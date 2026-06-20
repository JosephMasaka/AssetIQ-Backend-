<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRunItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_run_id',
        'invoice_id',
        'payment_amount',
        'discount_amount',
        'notes',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function paymentRun()
    {
        return $this->belongsTo(PaymentRun::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
