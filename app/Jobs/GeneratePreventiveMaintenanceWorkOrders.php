<?php

namespace App\Jobs;

use App\Models\PreventiveMaintenanceSchedule;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePreventiveMaintenanceWorkOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $schedules = PreventiveMaintenanceSchedule::where('is_active', true)
            ->where('auto_generate_wo', true)
            ->where('next_due_date', '<=', now()->addDays(7))
            ->get();

        foreach ($schedules as $schedule) {
            if ($this->shouldGenerateWorkOrder($schedule)) {
                $this->createWorkOrder($schedule);
                $this->updateSchedule($schedule);
            }
        }
    }

    protected function shouldGenerateWorkOrder(PreventiveMaintenanceSchedule $schedule): bool
    {
        $existingWO = WorkOrder::where('asset_id', $schedule->asset_id)
            ->where('type', 'preventive')
            ->where('scheduled_date', $schedule->next_due_date)
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->exists();

        return !$existingWO;
    }

    protected function createWorkOrder(PreventiveMaintenanceSchedule $schedule): void
    {
        WorkOrder::create([
            'work_order_number' => $this->generateWorkOrderNumber(),
            'title' => "Preventive Maintenance - {$schedule->schedule_name}",
            'description' => $schedule->tasks ?? "Scheduled preventive maintenance for {$schedule->asset->name}",
            'type' => 'preventive',
            'priority' => 'medium',
            'status' => 'open',
            'asset_id' => $schedule->asset_id,
            'location' => $schedule->asset?->location,
            'requested_by' => 1, // System
            'assigned_to' => $schedule->assigned_to,
            'requested_date' => now(),
            'scheduled_date' => $schedule->next_due_date,
            'due_date' => $schedule->next_due_date,
            'estimated_hours' => $schedule->estimated_duration,
            'estimated_cost' => $schedule->estimated_cost,
            'vendor_id' => $schedule->vendor_id,
            'notes' => $schedule->instructions,
            'company_id' => $schedule->company_id,
            'created_by' => 1,
        ]);
    }

    protected function updateSchedule(PreventiveMaintenanceSchedule $schedule): void
    {
        $nextDueDate = $this->calculateNextDueDate($schedule);

        $schedule->update([
            'last_performed_date' => $schedule->next_due_date,
            'next_due_date' => $nextDueDate,
        ]);
    }

    protected function calculateNextDueDate(PreventiveMaintenanceSchedule $schedule): Carbon
    {
        $current = Carbon::parse($schedule->next_due_date);

        return match ($schedule->frequency) {
            'daily' => $current->addDays($schedule->frequency_value),
            'weekly' => $current->addWeeks($schedule->frequency_value),
            'monthly' => $current->addMonths($schedule->frequency_value),
            'quarterly' => $current->addMonths($schedule->frequency_value * 3),
            'yearly' => $current->addYears($schedule->frequency_value),
            default => $current->addMonths(1),
        };
    }

    protected function generateWorkOrderNumber(): string
    {
        $prefix = 'WO-PM';
        $date = now()->format('Ymd');
        $sequence = str_pad(WorkOrder::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$date}-{$sequence}";
    }
}
