<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Models\DepreciationRun;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RunMonthlyDepreciation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $period;

    public function __construct(int $companyId, Carbon $period)
    {
        $this->companyId = $companyId;
        $this->period = $period;
    }

    public function handle(): void
    {
        DB::beginTransaction();
        try {
            $depreciationRun = DepreciationRun::firstOrCreate([
                'company_id' => $this->companyId,
                'period' => $this->period->format('Y-m-01'),
            ]);

            if ($depreciationRun->completed) {
                return;
            }

            $assets = Asset::where('company_id', $this->companyId)
                ->where('lifecycle_status', 'in_use')
                ->whereNotNull('useful_life_years')
                ->where('useful_life_years', '>', 0)
                ->get();

            $totalDepreciation = 0;

            foreach ($assets as $asset) {
                $monthlyDepreciation = $this->calculateMonthlyDepreciation($asset);

                if ($monthlyDepreciation > 0) {
                    $asset->increment('accumulated_depreciation', $monthlyDepreciation);
                    $asset->update([
                        'net_book_value' => $asset->purchase_cost - $asset->accumulated_depreciation
                    ]);

                    $totalDepreciation += $monthlyDepreciation;
                }
            }

            if ($totalDepreciation > 0) {
                $this->createDepreciationJournalEntry($totalDepreciation);
            }

            $depreciationRun->update(['completed' => true]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function calculateMonthlyDepreciation(Asset $asset): float
    {
        if (!$asset->acquisition_date || !$asset->useful_life_years) {
            return 0;
        }

        $depreciableAmount = $asset->purchase_cost - ($asset->salvage_value ?? 0);
        $monthlyDepreciation = $depreciableAmount / ($asset->useful_life_years * 12);

        $accumulatedSoFar = $asset->accumulated_depreciation ?? 0;

        if ($accumulatedSoFar + $monthlyDepreciation > $depreciableAmount) {
            return max(0, $depreciableAmount - $accumulatedSoFar);
        }

        return round($monthlyDepreciation, 2);
    }

    protected function createDepreciationJournalEntry(float $amount): void
    {
        $journalEntry = JournalEntry::create([
            'journal_number' => $this->generateJournalNumber(),
            'posting_date' => $this->period->endOfMonth(),
            'document_date' => $this->period->endOfMonth(),
            'document_type' => 'depreciation',
            'description' => 'Monthly depreciation for ' . $this->period->format('F Y'),
            'total_debit' => $amount,
            'total_credit' => $amount,
            'status' => 'posted',
            'posted_by' => 1, // System user
            'posted_at' => now(),
            'company_id' => $this->companyId,
            'created_by' => 1,
        ]);

        // Debit: Depreciation Expense
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'line_number' => 1,
            'gl_account_id' => $this->getDepreciationExpenseAccount(),
            'debit_credit' => 'debit',
            'amount' => $amount,
            'description' => 'Depreciation expense',
        ]);

        // Credit: Accumulated Depreciation
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'line_number' => 2,
            'gl_account_id' => $this->getAccumulatedDepreciationAccount(),
            'debit_credit' => 'credit',
            'amount' => $amount,
            'description' => 'Accumulated depreciation',
        ]);
    }

    protected function generateJournalNumber(): string
    {
        $prefix = 'JE-DEP';
        $date = now()->format('Ymd');
        $sequence = str_pad(JournalEntry::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$date}-{$sequence}";
    }

    protected function getDepreciationExpenseAccount(): int
    {
        // TODO: Get from GL mapping or settings
        return 1;
    }

    protected function getAccumulatedDepreciationAccount(): int
    {
        // TODO: Get from GL mapping or settings
        return 2;
    }
}
