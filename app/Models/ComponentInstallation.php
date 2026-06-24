<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentInstallation extends Model
{
    protected $fillable = [
        'company_id',
        'component_id',
        'asset_id',
        'install_position',
        'installed_at',
        'removed_at',
        'removal_reason',
        'installed_by',
        'removed_by',
        'notes',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function component()
    {
        return $this->belongsTo(Component::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function installedBy()
    {
        return $this->belongsTo(User::class, 'installed_by');
    }

    public function removedBy()
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    /** True if this installation record is the component's current/active one. */
    public function isActive(): bool
    {
        return $this->removed_at === null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('removed_at');
    }
}