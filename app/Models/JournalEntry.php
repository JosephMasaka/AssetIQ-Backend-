<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_number',
        'posting_date',
        'document_date',
        'document_type',
        'document_id',
        'reference_number',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'posted_by',
        'posted_at',
        'reversed_by_entry_id',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'posting_date' => 'date',
        'document_date' => 'date',
        'posted_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_number');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedByEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_by_entry_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
