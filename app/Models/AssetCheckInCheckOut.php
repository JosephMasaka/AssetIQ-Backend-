<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCheckinCheckout extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'checked_out_by',
        'checked_in_by',
        'checked_out_at',
        'checked_in_at',
        'assigned_to',
        'purpose',
        'status',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function checkedOutBy()
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
    