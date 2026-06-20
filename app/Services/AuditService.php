<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public static function log(
        string $eventType,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        AuditLog::logEvent($eventType, $model, $oldValues, $newValues, $description);
    }

    public static function logCreated(Model $model, ?string $description = null): void
    {
        static::log('created', $model, null, $model->getAttributes(), $description);
    }

    public static function logUpdated(Model $model, ?string $description = null): void
    {
        $changes = $model->getChanges();
        $original = array_intersect_key($model->getOriginal(), $changes);

        static::log('updated', $model, $original, $changes, $description);
    }

    public static function logDeleted(Model $model, ?string $description = null): void
    {
        static::log('deleted', $model, $model->getAttributes(), null, $description);
    }

    public static function logViewed(Model $model, ?string $description = null): void
    {
        static::log('viewed', $model, null, null, $description);
    }

    public static function logExported(string $entityType, array $filters, int $recordCount): void
    {
        $user = auth()->user();

        \App\Models\DataExportLog::create([
            'export_type' => 'data_extract',
            'entity_type' => $entityType,
            'filters' => $filters,
            'records_exported' => $recordCount,
            'file_format' => 'csv',
            'requested_by' => $user?->id,
            'ip_address' => request()->ip(),
            'company_id' => $user?->company_id,
        ]);
    }
}
