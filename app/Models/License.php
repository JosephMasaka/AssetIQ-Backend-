<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class License extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'version',
        'manufacturer',

        'seats_purchased',
        'seats_assigned',

        'purchase_order_number',
        'purchase_cost',
        'purchase_date',

        'license_type',
        'expiration_date',
        'is_renewable',

        'vendor_id',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiration_date' => 'date',
        'is_renewable' => 'boolean',
        'purchase_cost' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function assignments()
    {
        return $this->hasMany(
            LicenseAssignment::class
        );
    }

    public function assets()
    {
        return $this->morphedByMany(
            Asset::class,
            'assignable',
            'license_assignments'
        );
    }

    public function users()
    {
        return $this->morphedByMany(
            User::class,
            'assignable',
            'license_assignments'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getAvailableSeatsAttribute()
    {
        return max(
            0,
            $this->seats_purchased - $this->seats_assigned
        );
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiration_date &&
            $this->expiration_date->isPast();
    }
}