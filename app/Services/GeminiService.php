<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->apiUrl = config('services.gemini.api_url');
    }

    /**
     * Send prompt to Gemini API and return response
     */
    public function generateContent(string $prompt): array
    {
        try {
            if (empty($this->apiKey)) {
                Log::error('Gemini API Error: Missing API key.');
                return ['success' => false, 'error' => 'Missing Gemini API key'];
            }

            $endpoint = $this->apiUrl;
            if (!str_contains($endpoint, ':generateContent')) {
                $endpoint .= ':generateContent';
            }

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->post($endpoint, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 500,
                        'temperature' => 0.7,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API HTTP Error', ['status' => $response->status(), 'body' => $response->body()]);

                $errorMessage = trans('Gemini AI service is currently unavailable. Please try again later.');
                if ($response->status() === 429) {
                    $errorMessage = trans('Gemini API quota exceeded. Please check your Gemini API plan and billing details.');
                } elseif ($response->status() === 401 || $response->status() === 403) {
                    $errorMessage = trans('Invalid Gemini API key. Please check your API key in settings.');
                } elseif ($response->status() === 503 || $response->status() === 502 || $response->status() === 504) {
                    $errorMessage = trans('Gemini AI servers are currently offline or under maintenance. Please try again in a few minutes.');
                }

                return ['success' => false, 'error' => $errorMessage];
            }

            return ['success' => true, 'data' => $response->json()];

        } catch (ConnectionException $e) {
            Log::error('Gemini API Connection Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Gemini AI is currently unreachable. Please check your internet connection or try again later.'];
        } catch (Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return ['success' => false, 'error' => trans('Gemini AI service is currently unavailable. Please try again later.')];
        }
    }

    /**
     * Generate item description using Gemini AI
     */
    public function generateDescription(array $data): array
    {
        try {
            $prompt = $this->buildDescriptionPrompt($data);
            $promptHash = md5($prompt);

            $languageSuffix = $this->getLanguageSuffix($data);
            $cacheKey = "gemini_description_{$promptHash}{$languageSuffix}";

            $cached = Cache::store('gemini')->get($cacheKey);
            if ($cached) {
                return ['success' => true, 'data' => $cached, 'cached' => true];
            }

            $result = $this->generateContent($prompt);
            if (!$result['success']) {
                return $result;
            }

            $content = $this->extractText($result['data']);
            $content = $this->cleanResponse($content);

            Cache::store('gemini')->put($cacheKey, $content, 86400);

            return [
                'success' => true,
                'data' => $content,
                'cached' => false,
                'tokens_used' => $this->estimateTokens($prompt . $content),
            ];
        } catch (Exception $e) {
            Log::error('Gemini Description Generation Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to generate description: ' . $e->getMessage()];
        }
    }

    /**
     * Generate SEO meta details using Gemini AI
     */
    public function generateMetaDetails(array $data): array
    {
        try {
            $prompt = $this->buildMetaPrompt($data);
            $promptHash = md5($prompt);

            $languageSuffix = $this->getLanguageSuffix($data);
            $cacheKey = "gemini_meta_{$promptHash}{$languageSuffix}";

            $cached = Cache::store('gemini')->get($cacheKey);
            if ($cached) {
                return ['success' => true, 'data' => $cached, 'cached' => true];
            }

            $result = $this->generateContent($prompt);
            if (!$result['success']) {
                return $result;
            }

            $content = $this->extractText($result['data']);
            $content = $this->cleanResponse($content);

            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $response = [
                    'meta_title' => $decoded['meta_title'] ?? '',
                    'meta_description' => $decoded['meta_description'] ?? '',
                    'meta_keywords' => $decoded['meta_keywords'] ?? '',
                ];
            } else {
                $response = $this->parseMetaFromText($content);
            }

            Cache::store('gemini')->put($cacheKey, $response, 86400);

            return [
                'success' => true,
                'data' => $response,
                'cached' => false,
                'tokens_used' => $this->estimateTokens($prompt . json_encode($response)),
            ];
        } catch (Exception $e) {
            Log::error('Gemini Meta Generation Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to generate meta details: ' . $e->getMessage()];
        }
    }

    /**
     * Translate item fields (name, description, custom fields) into multiple target languages in a single API call
     */
    public function translateContent(array $data): array
    {
        try {
            $allLanguages = $data['target_languages'] ?? [];

            // Build code→id map for remapping response back to IDs
            $codeToId = [];
            foreach ($allLanguages as $l) {
                $codeToId[$l['code']] = (string) $l['id'];
            }

            $requestedIds = array_values(array_map(fn($l) => (string) $l['id'], $allLanguages));

            // Cache keyed on source content only — language-selection-agnostic
            $contentKey = md5(
                ($data['source_language'] ?? '') .
                ($data['name'] ?? '') .
                ($data['description'] ?? '') .
                json_encode($data['custom_fields'] ?? [])
            );
            $cacheKey = "gemini_translate_{$contentKey}";

            $cached = Cache::store('gemini')->get($cacheKey) ?? [];

            // Determine which IDs are missing from cache (empty name+description counts as missing)
            $validCachedIds = array_keys(array_filter($cached, fn($v) => !empty($v['name']) || !empty($v['description'])));
            $missingIds = array_diff($requestedIds, $validCachedIds);
            $missingLangs = array_values(array_filter($allLanguages, fn($l) => in_array((string) $l['id'], $missingIds)));

            if (empty($missingLangs)) {
                $subset = array_intersect_key($cached, array_flip($requestedIds));
                return ['success' => true, 'data' => $subset, 'cached' => true];
            }

            // Only call API for missing languages
            $data['target_languages'] = $missingLangs;
            $missingCodeToId = [];
            foreach ($missingLangs as $l) {
                $missingCodeToId[$l['code']] = (string) $l['id'];
            }

            $prompt = $this->buildTranslationPrompt($data);
            $result = $this->generateContentWithTokenLimit($prompt);
            
            if (!$result['success']) {
                return $result;
            }

            $content = $this->extractText($result['data']);
            $content = $this->cleanResponse($content);

            Log::info('Gemini translate raw response', [
                'missing_codes' => array_keys($missingCodeToId),
                'raw' => substr($content, 0, 1000),
            ]);

            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                Log::error('Gemini translate decode failed', ['raw' => substr($content, 0, 500)]);
                return ['success' => false, 'error' => 'Invalid translation response from AI'];
            }

            // Response is an array of {code, name, description, custom_fields?}
            // Remap each item's "code" → language ID
            $remapped = [];
            foreach ($decoded as $item) {
                if (!is_array($item) || empty($item['code'])) continue;
                $code = $item['code'];
                $id = $missingCodeToId[$code] ?? null;
                if ($id !== null) {
                    $remapped[$id] = array_filter([
                        'name'          => $item['name'] ?? '',
                        'description'   => $item['description'] ?? '',
                        'custom_fields' => $item['custom_fields'] ?? null,
                    ], fn($v) => $v !== null);
                }
            }

            if (empty($remapped)) {
                Log::warning('Gemini translation remap failed', [
                    'decoded' => $decoded,
                    'expected_codes' => array_keys($missingCodeToId),
                ]);
                return ['success' => false, 'error' => 'Translation response did not match expected languages. Please try again.'];
            }

            $merged = $cached + $remapped;
            Cache::store('gemini')->put($cacheKey, $merged, 86400);

            $subset = array_intersect_key($merged, array_flip($requestedIds));
            return ['success' => true, 'data' => $subset, 'cached' => false];
        } catch (Exception $e) {
            Log::error('Gemini Translation Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to translate content: ' . $e->getMessage()];
        }
    }

    public function buildTranslationPrompt(array $data): string
    {
        $sourceLang = $data['source_language'] ?? 'English';
        $targets = $data['target_languages'] ?? [];
        $hasCustom = !empty($data['custom_fields']);

        $langList = implode(', ', array_map(fn($t) => "{$t['name']} (code: {$t['code']})", $targets));

        // Truncate description to keep output manageable and avoid model timeouts
        $description = $data['description'] ?? '';
        $truncated = false;
        if (mb_strlen($description) > 600) {
            $description = mb_substr($description, 0, 600);
            $truncated = true;
        }
        $wordCount = str_word_count(strip_tags($description));

        $prompt = "You are a translator. TRANSLATE ONLY — do not rewrite, improve, add, or elaborate.\n\n";
        $prompt .= "Translate the name and description below from {$sourceLang} into: {$langList}.\n\n";

        $prompt .= "--- SOURCE ({$sourceLang}) ---\n";
        $prompt .= "name: " . json_encode($data['name'] ?? '') . "\n";
        $prompt .= "description: " . json_encode($description) . "\n";
        if ($hasCustom) {
            $prompt .= "custom_fields: " . json_encode($data['custom_fields']) . "\n";
        }
        $prompt .= "--- END SOURCE ---\n\n";

        $prompt .= "STRICT RULES:\n";
        $prompt .= "1. Output a JSON array with exactly " . count($targets) . " object(s)\n";
        $prompt .= "2. Each object: {\"code\": \"<lang_code>\", \"name\": \"<translation>\", \"description\": \"<translation>\"" . ($hasCustom ? ", \"custom_fields\": {<same_keys_translated>}" : "") . "}\n";
        $prompt .= "3. \"code\" values must be exactly: " . implode(', ', array_map(fn($t) => "\"{$t['code']}\"", $targets)) . "\n";
        $prompt .= "4. Description translation must be approximately {$wordCount} words — same length as source, no additions\n";
        $prompt .= "5. Preserve all formatting (bold **text**, line breaks \\n) from the source\n";
        $prompt .= "6. custom_fields: numeric-only values stay as-is; translate text values\n";
        $prompt .= "7. JSON array ONLY — no markdown fences, no explanation, no extra text";

        return $prompt;
    }

    private function generateContentWithTokenLimit(string $prompt): array
    {
        try {
            if (empty($this->apiKey)) {
                return ['success' => false, 'error' => 'Missing Gemini API key'];
            }

            $endpoint = $this->apiUrl;
            if (!str_contains($endpoint, ':generateContent')) {
                $endpoint .= ':generateContent';
            }

            $response = Http::timeout(60)
                ->connectTimeout(5)
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->post($endpoint, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API HTTP Error (translation)', ['status' => $response->status(), 'body' => $response->body()]);

                $errorMessage = trans('Gemini AI service is currently unavailable. Please try again later.');
                if ($response->status() === 429) {
                    $errorMessage = trans('Gemini API quota exceeded. Please check your Gemini API plan and billing details.');
                } elseif ($response->status() === 401 || $response->status() === 403) {
                    $errorMessage = trans('Invalid Gemini API key. Please check your API key in settings.');
                } elseif ($response->status() === 503 || $response->status() === 502 || $response->status() === 504) {
                    $errorMessage = trans('Gemini AI servers are currently offline or under maintenance. Please try again in a few minutes.');
                }
                return ['success' => false, 'error' => $errorMessage];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (ConnectionException $e) {
            Log::error('Gemini API Connection Error (translation): ' . $e->getMessage());
            return ['success' => false, 'error' => trans('Gemini AI is currently unreachable. Please check your internet connection or try again later.')];
        } catch (Exception $e) {
            Log::error('Gemini API Error (translation): ' . $e->getMessage());
            return ['success' => false, 'error' => trans('Gemini AI service is currently unavailable. Please try again later.')];
        }
    }

    /**
     * Check if cached description exists
     */
    public function hasCachedDescription(array $data): bool
    {
        $prompt = $this->buildDescriptionPrompt($data);
        $cacheKey = "gemini_description_" . md5($prompt) . $this->getLanguageSuffix($data);
        return Cache::store('gemini')->has($cacheKey);
    }

    /**
     * Check if cached meta details exist
     */
    public function hasCachedMetaDetails(array $data): bool
    {
        $prompt = $this->buildMetaPrompt($data);
        $cacheKey = "gemini_meta_" . md5($prompt) . $this->getLanguageSuffix($data);
        return Cache::store('gemini')->has($cacheKey);
    }

    /**
     * Build description generation prompt
     */
    public function buildDescriptionPrompt(array $data): string
    {
        $prompt = "Write an SEO-friendly item listing description (200-300 words).\n";

        // Add data first
        $prompt .= "\nTitle: " . trim($data['title'] ?? 'N/A');

        $fields = [];
        if (!empty($data['location'])) $fields[] = "Location: {$data['location']}";
        if (!empty($data['city'])) $fields[] = "City: {$data['city']}";
        if (!empty($data['state'])) $fields[] = "State: {$data['state']}";
        if (!empty($data['country'])) $fields[] = "Country: {$data['country']}";
        if (!empty($data['price'])) $fields[] = "Price: {$data['price']}";
        if (!empty($data['category_name'])) $fields[] = "Category: {$data['category_name']}";
        if (!empty($data['currency_iso_code'])) {
            $currency = $data['currency_iso_code'];
            $fields[] = "Currency: {$currency}";
            $prompt .= "\n\nIMPORTANT: Write the description in {$currency} currency using its symbol.";
        } else {
            $currency = CachingService::getSystemSettings('currency_iso_code');
            $fields[] = "Currency: {$currency}";
            $prompt .= "\n\nIMPORTANT: Write the description in {$currency} currency using its symbol.";
        }

        if (!empty($fields)) {
            $prompt .= "\n" . implode("\n", $fields);
        }

        // Strong constraints AFTER data
        $prompt .= "\n\nInstructions:";
        $prompt .= "\n- Write in an engaging and professional tone";
        $prompt .= "\n- Highlight features and location benefits";
        $prompt .= "\n- Text only (no bullets, no emojis)";
        $prompt .= "\n- DO NOT assume or generate missing details";

        if (!empty($data['price'])) {
            $currency = $data['currency_iso_code'] ?? CachingService::getSystemSettings('currency_iso_code');
            $prompt .= "\n- MUST mention the price {$data['price']} naturally within the description using the {$currency} currency symbol";
        } else {
            $prompt .= "\n- DO NOT include any price since none was provided";
        }

        // Language
        if (!empty($data['language_name']) || !empty($data['language_code'])) {
            $language = $data['language_name'] ?? $data['language_code'];
            $prompt .= "\n- The entire description must be in {$language}";
        }

        return $prompt;
    }

    /**
     * Build SEO meta prompt
     */
    public function buildMetaPrompt(array $data): string
    {
        $prompt = "You are an SEO assistant. Based on the following item listing data, generate SEO meta details.\n\n";

        if (!empty($data['language_name']) || !empty($data['language_code'])) {
            $language = $data['language_name'] ?? $data['language_code'];
            $prompt .= "IMPORTANT: Write the meta details in {$language} language.\n\n";
        }

        $prompt .= "Title: " . ($data['title'] ?? 'N/A') . "\n";
        
        if (!empty($data['category_name'])) $prompt .= "Category: {$data['category_name']}\n";
        if (!empty($data['location'])) $prompt .= "Location: {$data['location']}\n";
        if (!empty($data['city'])) $prompt .= "City: {$data['city']}\n";
        if (!empty($data['state'])) $prompt .= "State: {$data['state']}\n";
        if (!empty($data['country'])) $prompt .= "Country: {$data['country']}\n";
        if (!empty($data['price'])) $prompt .= "Price: {$data['price']}\n";
        if (!empty($data['currency_iso_code'])) {
            $currency = $data['currency_iso_code'];
            $prompt .= "\n\nIMPORTANT: Write the meta details in {$currency} currency using its symbol.";
        }else{
            $currency = CachingService::getSystemSettings('currency_iso_code');
            $prompt .= "\n\nIMPORTANT: Write the meta details in {$currency} currency using its symbol.";
        }

        $prompt .= "\nReturn ONLY a valid JSON object with this exact structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"meta_title\": \"...\",\n";
        $prompt .= "  \"meta_description\": \"...\",\n";
        $prompt .= "  \"meta_keywords\": \"...\"\n";
        $prompt .= "}\n\n";
        $prompt .= "Rules:\n";
        $prompt .= "- meta_title: 50-60 characters, include location and key feature\n";
        $prompt .= "- meta_description: 150-160 characters, compelling and clear, based on the listing data above\n";
        $prompt .= "- meta_keywords: 10-15 comma-separated keywords, based on the listing data above\n";
        $prompt .= "- Do NOT add any explanation, markdown, or extra text. JSON ONLY.";

        return $prompt;
    }

    private function extractText(array $data): string
    {
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function cleanResponse(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        return trim($content, " \t\n\r\0\x0B\"'");
    }

    private function parseMetaFromText(string $content): array
    {
        if (preg_match('/\{[^}]+\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return [
                    'meta_title' => $json['meta_title'] ?? '',
                    'meta_description' => $json['meta_description'] ?? '',
                    'meta_keywords' => $json['meta_keywords'] ?? '',
                ];
            }
        }

        $meta = ['meta_title' => '', 'meta_description' => '', 'meta_keywords' => ''];
        foreach (explode("\n", $content) as $line) {
            if (stripos($line, 'title') !== false) {
                $meta['meta_title'] = trim(str_replace(['Title:', 'Meta Title:'], '', $line));
            } elseif (stripos($line, 'description') !== false) {
                $meta['meta_description'] = trim(str_replace(['Description:', 'Meta Description:'], '', $line));
            } elseif (stripos($line, 'keyword') !== false) {
                $meta['meta_keywords'] = trim(str_replace(['Keywords:', 'Meta Keywords:'], '', $line));
            }
        }

        return $meta;
    }

    private function getLanguageSuffix(array $data): string
    {
        if (!empty($data['language_code'])) {
            return '_' . $data['language_code'];
        }
        if (!empty($data['language_name'])) {
            return '_' . md5($data['language_name']);
        }
        return '';
    }

    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
