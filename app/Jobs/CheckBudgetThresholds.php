<?php

namespace App\Jobs;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckBudgetThresholds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $activeBudgets = Budget::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get();

        foreach ($activeBudgets as $budget) {
            $this->checkBudgetUtilization($budget);
        }
    }

    protected function checkBudgetUtilization(Budget $budget): void
    {
        if ($budget->total_budget <= 0) {
            return;
        }

        $utilizationPercentage = ($budget->actual_amount / $budget->total_budget) * 100;

        // Alert at 75%, 90%, 95%, and 100%
        $thresholds = [
            100 => 'critical',
            95 => 'warning',
            90 => 'warning',
            75 => 'info',
        ];

        foreach ($thresholds as $threshold => $level) {
            if ($utilizationPercentage >= $threshold) {
                $this->sendBudgetAlert($budget, $utilizationPercentage, $threshold, $level);
                break;
            }
        }
    }

    protected function sendBudgetAlert(Budget $budget, float $utilization, int $threshold, string $level): void
    {
        $message = "Budget '{$budget->name}' has reached {$utilization}% utilization (threshold: {$threshold}%)";

        Log::log($level, $message, [
            'budget_id' => $budget->id,
            'budget_code' => $budget->budget_code,
            'total_budget' => $budget->total_budget,
            'actual_amount' => $budget->actual_amount,
            'available_amount' => $budget->available_amount,
            'utilization_percentage' => round($utilization, 2),
            'threshold' => $threshold,
        ]);

        // TODO: Send email to budget owner and finance team
        // TODO: Create in-app notification
    }
}
