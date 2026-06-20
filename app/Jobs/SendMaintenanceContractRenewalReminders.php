<?php

namespace App\Jobs;

use App\Models\MaintenanceContract;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMaintenanceContractRenewalReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $reminderDays = [90, 60, 30, 15];

        foreach ($reminderDays as $days) {
            $targetDate = now()->addDays($days)->format('Y-m-d');

            $contracts = MaintenanceContract::where('end_date', $targetDate)
                ->where('status', 'active')
                ->get();

            foreach ($contracts as $contract) {
                $this->sendReminderNotification($contract, $days);
            }
        }

        // Update expired contracts
        $expiredContracts = MaintenanceContract::where('end_date', '<', now())
            ->where('status', 'active')
            ->get();

        foreach ($expiredContracts as $contract) {
            $contract->update(['status' => 'expired']);
            $this->sendExpiryNotification($contract);
        }
    }

    protected function sendReminderNotification(MaintenanceContract $contract, int $daysUntilExpiry): void
    {
        $message = "Maintenance contract '{$contract->contract_name}' with {$contract->vendor->name} will expire in {$daysUntilExpiry} days on {$contract->end_date->format('Y-m-d')}";

        Log::info($message, [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'vendor' => $contract->vendor->name,
            'end_date' => $contract->end_date,
            'days_until_expiry' => $daysUntilExpiry,
            'auto_renew' => $contract->auto_renew,
        ]);

        // TODO: Send email to contract manager
        // TODO: Create in-app notification
    }

    protected function sendExpiryNotification(MaintenanceContract $contract): void
    {
        $message = "Maintenance contract '{$contract->contract_name}' with {$contract->vendor->name} has expired";

        Log::warning($message, [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'vendor' => $contract->vendor->name,
            'end_date' => $contract->end_date,
        ]);

        // TODO: Send urgent email notification
    }
}
