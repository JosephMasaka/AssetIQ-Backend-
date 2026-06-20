<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_run_number',
        'payment_date',
        'value_date',
        'payment_method',
        'status',
        'total_amount',
        'invoice_count',
        'bank_account_id',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'company_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'value_date' => 'date',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PaymentRunItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
