<?php

namespace App\Services;

use App\Models\Item;
use App\Models\UserPurchasedPackage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\CachingService;

class ExpiringItemService
{
    /**
     * Send notifications for expiring items and packages.
     */
    public function notifyExpiringItemsAndPackages()
    {
        $this->notifyExpiringItems();
        $this->notifyExpiringPackages();
    }

    /**
     * Send notifications for expiring items.
     */
    public function notifyExpiringItems()
    {
        $twoDaysFromNow = Carbon::now()->addDays(2)->startOfDay();
        $items = Item::whereDate('expiry_date', '=', $twoDaysFromNow)
            ->where('status', 'approved')
            ->get();
        $skippedCount = 0;

        foreach ($items as $item) {
            $user = $item->user;
            if ($user && $user->email) {
                $this->sendNotification($user, $item);
            } else {
                $skippedCount++;
            }
        }

        if ($skippedCount > 0) {
            Log::warning("Skipped {$skippedCount} item notifications due to missing user or email");
        }
    }

    /**
     * Send notifications for expiring packages.
     */
    public function notifyExpiringPackages()
    {
        $twoDaysFromNow = Carbon::now()->addDays(2)->startOfDay();
        $packages = UserPurchasedPackage::whereDate('end_date', '=', $twoDaysFromNow)->get();
        $skippedCount = 0;

        foreach ($packages as $package) {
            $user = $package->user;
            $packageDetails = $package->package;

            if ($user && $user->email && $packageDetails) {
                $this->sendPackageNotification($user, $packageDetails, $package);
            } else {
                $skippedCount++;
            }
        }

        if ($skippedCount > 0) {
            Log::warning("Skipped {$skippedCount} package notifications due to missing user, email, or package details");
        }
    }

    /**
     * Send email + push notification for expiring items.
     */
    protected function sendNotification(User $user, $item)
    {
        try {
            $expiryDate = Carbon::parse($item->expiry_date)->format('d M Y');
            $companyName = CachingService::getSystemSettings('company_name') ?? 'Admin';
            $adminMail = env('MAIL_FROM_ADDRESS', 'admin@yourdomain.com');

            // Get email template (uses default language from admin settings)
            $emailContent = NotificationService::processEmailTemplate('email_template_item_expiry', [
                'user_name' => $user->name ?? 'User',
                'item_name' => $item->name,
                'expiry_date' => $expiryDate,
                'company_name' => $companyName,
            ]);

            // Fallback message if template is empty
            $message = "Your advertisement '{$item->name}' is expiring on {$expiryDate}. Please take action before it expires.";
            
            if (empty($emailContent)) {
                $emailContent = "Hello {$user->name},\n\n{$message}";
            }

            // ✅ Send Email
            NotificationService::sendMail($emailContent, $user->email, 'Advertisement Expiring Soon', $user->name, $adminMail, $companyName);

            // ✅ Send Push Notification
            if (!empty($user->id)) {
                $fcmMsg = [
                    'item_id' => $item->id,
                    'type' => 'item_expiry',
                    'expiry_date' => $item->expiry_date,
                ];
                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    'Advertisement Expiring Soon',
                    $message,
                    'item_expiry',
                    $fcmMsg,
                    false,
                    array($user->id)
                );

                // NotificationService::sendFcmNotification(
                //     $user_tokens,
                //     'Advertisement Expiring Soon',
                //     $message,
                //     'item_expiry',
                //     $fcmMsg
                // );
            }

            Log::info("Expiry notification sent to: {$user->email} for Advertisement: {$item->name}");

        } catch (\Exception $e) {
            Log::error("Failed to send notification for Advertisement {$item->id}: " . $e->getMessage());
        }
    }

    /**
     * Send email + push notification for expiring packages.
     */
    protected function sendPackageNotification(User $user, $package, $userPackage)
    {
        try {
            $expiryDate = Carbon::parse($userPackage->end_date)->format('d M Y');
            $companyName = CachingService::getSystemSettings('company_name') ?? 'Admin';
            $adminMail = env('MAIL_FROM_ADDRESS', 'admin@yourdomain.com');

            // Get email template (uses default language from admin settings)
            $emailContent = NotificationService::processEmailTemplate('email_template_package_expiry', [
                'user_name' => $user->name ?? 'User',
                'package_name' => $package->name,
                'expiry_date' => $expiryDate,
                'company_name' => $companyName,
            ]);

            // Fallback message if template is empty
            $message = "Your subscription package '{$package->name}' is expiring on {$expiryDate}. Please renew or upgrade your subscription.";
            
            if (empty($emailContent)) {
                $emailContent = "Hello {$user->name},\n\n{$message}";
            }

            // ✅ Send Email
            NotificationService::sendMail($emailContent, $user->email, 'Package Expiring Soon', $user->name, $adminMail, $companyName);

            // ✅ Send Push Notification
            if (!empty($user->id)) {
                $fcmMsg = [
                    'package_id' => $package->id,
                    'type' => 'package_expiry',
                    'expiry_date' => $userPackage->end_date,
                ];

                NotificationService::dispatchChunkedNotifications(
                    'Package Expiring Soon',
                    $message,
                    'package_expiry',
                    $fcmMsg,
                    false,
                    array($user->id)
                );
                // NotificationService::sendFcmNotification(
                //     $user_tokens,
                //     'Package Expiring Soon',
                //     $message,
                //     'package_expiry',
                //     $fcmMsg
                // );
            }

            Log::info("Package expiry notification sent to: {$user->email} for package: {$package->name}");

        } catch (\Exception $e) {
            Log::error("Failed to send notification for Package {$userPackage->id}: " . $e->getMessage());
        }
    }
}
