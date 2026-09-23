<?php

namespace App\Services;

use Throwable;
use Google\Client;
use GuzzleHttp\Pool;
use App\Models\Setting;
use App\Models\Language;
use App\Models\UserFcmToken;
use GuzzleHttp\Psr7\Request;
use App\Jobs\ProcessFcmChunkJob;
use App\Jobs\SendHtmlMailJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Storage;

class NotificationService
{
    /**
     * Dispatch chunked FCM notification jobs.
     * 
     * This method gathers FCM tokens based on the criteria, chunks them,
     * and dispatches individual ProcessFcmChunkJob instances for each chunk.
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type (e.g., 'notification', 'blog')
     * @param array $customBodyFields Custom fields to include in the notification payload
     * @param bool $sendToAll Whether to send to all users with notifications enabled
     * @param array $userIds Array of specific user IDs (used when $sendToAll is false)
     * @param int $chunkSize Number of tokens per chunk (default: 500, FCM batch limit)
     * @return array Returns info about dispatched jobs ['success' => bool, 'jobs_count' => int, 'tokens_count' => int, 'message' => string]
     */
    public static function dispatchChunkedNotifications(
        string $title,
        string $message,
        string $type = 'default',
        array $customBodyFields = [],
        bool $sendToAll = false,
        array $userIds = [],
        bool $skipQueue = false,
        int $chunkSize = 200,
    ): array {
        try {
            // Fetch FCM tokens based on send_to parameter
            if ($sendToAll) {
                $tokens = UserFcmToken::with('user')
                    ->whereHas('user', fn($q) => $q->where('notification', 1))
                    ->pluck('fcm_token')->toArray();
            } else {
                if (empty($userIds)) {
                    Log::warning('⚠️ No user IDs provided for targeted notification');
                    return [
                        'success' => false,
                        'jobs_count' => 0,
                        'tokens_count' => 0,
                        'message' => 'No user IDs provided'
                    ];
                }
                
                $tokens = UserFcmToken::with('user')
                    ->whereHas('user', fn($q) => $q->where('notification', 1))
                    ->whereIn('user_id', $userIds)
                    ->pluck('fcm_token')->toArray();
            }

            $totalTokens = count($tokens);
            
            if ($totalTokens === 0) {
                Log::info('ℹ️ No FCM tokens found for notification');
                return [
                    'success' => true,
                    'jobs_count' => 0,
                    'tokens_count' => 0,
                    'message' => 'No users with FCM tokens found'
                ];
            }

            Log::info("📊 Total FCM tokens to send: {$totalTokens}");

            // If token count is small (< 200), send immediately without queuing
            $sendImmediately = $totalTokens < 200;

            if ($sendImmediately) {
                Log::info("⚡ Token count ({$totalTokens}) is less than 200 — sending immediately without queue");
            }

            // Split tokens into chunks and dispatch separate jobs for each chunk
            $chunks = collect($tokens)->chunk($chunkSize);
            $chunkCount = $chunks->count();

            // Dispatch a separate job for each chunk
            $chunks->each(function ($chunk) use ($title, $message, $customBodyFields, $type, $skipQueue, $sendImmediately) {
                $job = new ProcessFcmChunkJob(
                    $chunk->toArray(),
                    $title,
                    $message,
                    $customBodyFields,
                    $type
                );
                
                if ($skipQueue || $sendImmediately) {
                    dispatch_sync($job);
                } else {
                    dispatch($job);
                }
            });

            $message = "Successfully dispatched {$chunkCount} notification jobs for {$totalTokens} users";
            Log::info("✅ {$message}");

            return [
                'success' => true,
                'jobs_count' => $chunkCount,
                'tokens_count' => $totalTokens,
                'message' => $message
            ];
        } catch (Throwable $th) {
            Log::error('🚨 Failed to dispatch chunked notifications: ' . $th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            
            return [
                'success' => false,
                'jobs_count' => 0,
                'tokens_count' => 0,
                'message' => 'Failed to dispatch notifications: ' . $th->getMessage()
            ];
        }
    }
    /**
     * Send FCM notification to specific tokens or via topic.
     */
    public static function sendFcmNotification(
        array $registrationIDs = [],
        ?string $title = '',
        ?string $message = '',
        array $customBodyFields = [],
        string $type = 'default',
        // bool $sendToAll = false
    ): bool|array {
        try {
            $projectId = Setting::where('name', 'firebase_project_id')->value('value');
            if (empty($projectId)) {
                Log::error('FCM Project ID not configured');
                return ['error' => true, 'message' => 'FCM Project ID not configured.'];
            }

            $accessToken = Cache::remember('fcm_access_token', 3500, function () {
                return self::getAccessToken();
            });

            if (!isset($accessToken['data']) || $accessToken['error']) {
                return $accessToken;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken['data'],
                'Content-Type'  => 'application/json',
            ];

            $payloadBase = [
                ...$customBodyFields,
                'title' => $title,
                'body'  => $message,
                'type'  => $type,
            ];

            $client = new GuzzleClient();

            // ✅ Case 1: Topic-based send (all users)
            // if ($sendToAll) {
            //     $data = [
            //         'message' => [
            //             'topic' => 'allUsers',
            //             'notification' => [
            //                 'title' => $title,
            //                 'body'  => $message,
            //                 'image' => $customBodyFields['image'] ?? null,
            //             ],
            //             'data' => self::convertToStringRecursively($payloadBase),
            //         ],
            //     ];

            //     $client->post($url, ['headers' => $headers, 'json' => $data]);
            //     Log::info('📢 Sent FCM topic notification to allUsers topic');
            //     return ['error' => false, 'message' => 'Topic notification sent'];
            // }

            // ✅ Case 2: Individual tokens (with concurrency)
            if (empty($registrationIDs)) {
                Log::info('⚠️ No registration tokens provided.');
                return ['error' => true, 'message' => 'No tokens provided'];
            }

            $requests = [];
            foreach ($registrationIDs as $token) {
                $data = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body'  => $message,
                            'image' => $customBodyFields['image'] ?? null,
                        ],
                        'data' => self::convertToStringRecursively($payloadBase),
                    ],
                ];
                $requests[] = new Request('POST', $url, $headers, json_encode($data));
            }

            $unregistered = [];

            Log::info('Total tokens: ' . count($registrationIDs));

            $count = 0;

            $pool = new Pool($client, $requests, [
                'concurrency' => 10,
                'fulfilled' => function () use (&$count) {
                    $count++;
                },
                'rejected' => function ($reason, $index) use (&$registrationIDs, &$unregistered) {
                    $response = $reason->getResponse();
                    if ($response) {
                        $decoded = json_decode($response->getBody(), true);
                        $errorMsg = strtolower($decoded['error']['message'] ?? '');
                        $isInvalid = str_contains($errorMsg, 'invalid registration token')
                            || str_contains($errorMsg, 'not a valid')
                            || ($decoded['error']['status'] ?? '') === 'NOT_FOUND';

                        if ($isInvalid) {
                            $unregistered[] = $registrationIDs[$index] ?? null;
                        }
                    }
                },
            ]);

            $pool->promise()->wait();
            Log::info('Total tokens sent: ' . $count);

            if (!empty($unregistered)) {
                UserFcmToken::whereIn('fcm_token', array_filter($unregistered))->delete();
                Log::info("🧹 Deleted invalid tokens: " . count($unregistered));
            }


            Log::info('✅ FCM notifications sent successfully (' . count($registrationIDs) . ' tokens)');
            return ['error' => false, 'message' => 'Individual notifications sent'];
        } catch (Throwable $th) {
            Log::error('🚨 FCM send error: ' . $th->getMessage());
            return ['error' => true, 'message' => $th->getMessage()];
        }
    }

    /**
     * Generate & cache access token using Firebase service file.
     */
    public static function getAccessToken(): array
    {
        try {
            $file = Setting::where('name', 'service_file')->value('value');
            if (!$file) {
                Log::error('Firebase service file not configured');
                return ['error' => true, 'message' => 'Firebase service file not configured'];
            }

            $disk = config('filesystems.default');
            if (in_array($disk, ['local', 'public'])) {
                $filePath = Storage::disk($disk)->path($file);
            } else {
                $fileContent = Storage::disk($disk)->get($file);
                $filePath = storage_path('app/firebase_service.json');
                file_put_contents($filePath, $fileContent);
            }

            if (!file_exists($filePath)) {
                Log::error('Firebase service file is missing');
                return ['error' => true, 'message' => 'Firebase service file missing'];
            }

            $client = new Client();
            $client->setAuthConfig($filePath);
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

            $token = $client->fetchAccessTokenWithAssertion();
            return [
                'error' => false,
                'data' => $token['access_token'] ?? null,
                'message' => 'Access token generated',
            ];
        } catch (Throwable $e) {
            Log::error('Access token generation failed: ' . $e->getMessage());
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    /**
     * Convert all array values to strings (required for FCM data payloads).
     */
    public static function convertToStringRecursively(array $data, array &$flat = []): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                self::convertToStringRecursively($value, $flat);
            } elseif (is_null($value)) {
                $flat[$key] = '';
            } else {
                $flat[$key] = (string) $value;
            }
        }
        return $flat;
    }

    /**
     * Get default language ID from admin settings.
     * 
     * @return int|null Language ID or null if not found
     */
    public static function getDefaultLanguageId(): ?int
    {
        try {
            $defaultLanguageCode = CachingService::getSystemSettings('default_language') ?? 'en';
            $language = Language::where('code', $defaultLanguageCode)->first();
            return $language ? $language->id : null;
        } catch (Throwable $e) {
            Log::error("Failed to get default language: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process email template by replacing variables.
     * Uses default language from admin settings for email templates.
     * 
     * @param string $templateName Setting name for the template
     * @param array $variables Array of variables to replace (e.g., ['user_name' => 'John', 'item_name' => 'Product'])
     * @param int|null $languageId Optional language ID to get translated template. If null, uses default language from settings.
     * @return string Processed email content
     */
    public static function processEmailTemplate(string $templateName, array $variables = [], ?int $languageId = null): string
    {
        try {
            $template = null;
            
            // Use default language from settings if no language ID provided
            if ($languageId === null) {
                $languageId = self::getDefaultLanguageId();
            }
            
            $setting = \App\Models\Setting::with('translations')
                ->where('name', $templateName)
                ->first();
            
            if ($setting && $setting->relationLoaded('translations')) {
                // Try to get translated template for default language
                if ($languageId !== null) {
                    $translation = $setting->translations
                        ->where('language_id', $languageId)
                        ->where('key', 'translated_value')
                        ->first();

                    if ($translation && !empty($translation->value)) {
                        $template = $translation->value;
                    }
                }

                // If no template found in default language, try English (en) as fallback
                if (empty($template)) {
                    $englishLanguage = Language::where('code', 'en')->first();
                    if ($englishLanguage) {
                        $englishTranslation = $setting->translations
                            ->where('language_id', $englishLanguage->id)
                            ->where('key', 'translated_value')
                            ->first();

                        if ($englishTranslation && !empty($englishTranslation->value)) {
                            $template = $englishTranslation->value;
                        }
                    }
                }
            }
            
            // Final fallback to default template (base value from settings table)
            if (empty($template)) {
                $template = CachingService::getSystemSettings($templateName);
            }
            
            if (empty($template)) {
                Log::warning("Email template '{$templateName}' not found, using default");
                return '';
            }

            // Replace variables in template
            foreach ($variables as $key => $value) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }

            return $template;
        } catch (Throwable $e) {
            Log::error("Failed to process email template '{$templateName}': " . $e->getMessage());
            return '';
        }
    }

    /**
     * Queue an HTML email by default; pass $skipQueue = true to send it synchronously.
     */
    public static function sendMail(string $html, string $to, string $subject, ?string $toName = null, ?string $fromAddress = null, ?string $fromName = null, bool $skipQueue = false): void
    {
        $job = new SendHtmlMailJob($html, $to, $toName, $fromAddress, $fromName, $subject);

        if ($skipQueue) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }

    /**
     * Send new device login alert email.
     */
    public static function sendNewDeviceLoginEmail($user, $request, bool $skipQueue = false): void
    {
        try {
            // Check if new login email is enabled
            $enabled = CachingService::getSystemSettings('email_new_login_enabled');
            if (empty($enabled) || $enabled != '1') {
                Log::info("New login email notification is disabled, skipping email to {$user->email}");
                return;
            }

            $deviceType = ucfirst($request->platform_type ?? 'Unknown');
            $ip = request()->ip();
            $loginTime = now()->format('d M Y - h:i A');
            $companyName = CachingService::getSystemSettings('company_name') ?? 'Unknown';

            // Get email template (uses default language from admin settings)
            $emailContent = self::processEmailTemplate('email_template_new_login', [
                'user_name' => $user->name ?? 'User',
                'device_type' => $deviceType,
                'ip_address' => $ip,
                'login_time' => $loginTime,
                'company_name' => $companyName,
            ]);

            // Fallback to default if template is empty
            if (empty($emailContent)) {
                $emailContent = "Hello {$user->name},\n\n"
                    . "A new device has logged in to your {$companyName} account.\n\n"
                    . "Device: {$deviceType}\n"
                    . "IP: {$ip}\n"
                    . "Time: {$loginTime}\n\n"
                    . "If this was not you, please secure your account immediately.\n\n"
                    . "Best regards,\n{$companyName}";
            }

            $adminMail = env('MAIL_FROM_ADDRESS');
            self::sendMail($emailContent, $user->email, "New Device Login Detected - {$companyName}", $user->name, $adminMail, $companyName, $skipQueue);

            Log::info("📧 Device login alert sent to {$user->email}");
        } catch (Throwable $e) {
            Log::error('Failed to send new device login email: ' . $e->getMessage());
        }
    }
}
