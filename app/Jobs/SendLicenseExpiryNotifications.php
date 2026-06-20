<?php

namespace App\Jobs;

use App\Models\License;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLicenseExpiryNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $notificationThresholds = [90, 60, 30, 14, 7, 1];

        foreach ($notificationThresholds as $days) {
            $targetDate = now()->addDays($days)->format('Y-m-d');

            $licenses = License::where('expiration_date', $targetDate)
                ->where('status', 'active')
                ->get();

            foreach ($licenses as $license) {
                $this->sendNotification($license, $days);
            }
        }

        // Also check for already expired licenses
        $expiredLicenses = License::where('expiration_date', '<', now())
            ->where('status', 'active')
            ->get();

        foreach ($expiredLicenses as $license) {
            $license->update(['status' => 'expired']);
            $this->sendNotification($license, 0);
        }
    }

    protected function sendNotification(License $license, int $daysUntilExpiry): void
    {
        $message = $daysUntilExpiry > 0
            ? "License '{$license->name}' will expire in {$daysUntilExpiry} days on {$license->expiration_date->format('Y-m-d')}"
            : "License '{$license->name}' has expired on {$license->expiration_date->format('Y-m-d')}";

        Log::info($message, [
            'license_id' => $license->id,
            'license_name' => $license->name,
            'expiration_date' => $license->expiration_date,
            'days_until_expiry' => $daysUntilExpiry,
        ]);

        // TODO: Send email notification to license manager
        // TODO: Create in-app notification
    }
}
