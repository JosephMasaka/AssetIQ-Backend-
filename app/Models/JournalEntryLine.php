<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'line_number',
        'gl_account_id',
        'cost_center_id',
        'debit_credit',
        'amount',
        'description',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function glAccount()
    {
        return $this->belongsTo(GeneralLedger::class, 'gl_account_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
