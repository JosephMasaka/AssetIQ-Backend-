<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'event_type',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'description',
        'module',
        'company_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public static function logEvent(
        string $eventType,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        $user = auth()->user();

        static::create([
            'event_type' => $eventType,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'module' => static::getModuleFromModel($model),
            'company_id' => $user?->company_id ?? $model->company_id ?? null,
        ]);
    }

    protected static function getModuleFromModel(Model $model): string
    {
        $class = class_basename($model);

        return match (true) {
            str_contains($class, 'Asset') => 'assets',
            str_contains($class, 'Requisition') || str_contains($class, 'Purchase') => 'procurement',
            str_contains($class, 'Budget') || str_contains($class, 'Journal') || str_contains($class, 'Invoice') => 'finance',
            str_contains($class, 'Maintenance') || str_contains($class, 'WorkOrder') => 'maintenance',
            default => 'system',
        };
    }
}
