<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicenseAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'assignable_type',
        'assignable_id',
        'assigned_at',
        'revoked_at',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'revoked_at' => 'date',
    ];

    public function license()
    {
        return $this->belongsTo(
            License::class
        );
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}