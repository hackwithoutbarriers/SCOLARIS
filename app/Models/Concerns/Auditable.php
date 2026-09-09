<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model): void {
            $model->writeAudit('created', [], $model->getAttributes());
        });

        static::updated(function ($model): void {
            $model->writeAudit('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model): void {
            $model->writeAudit('deleted', $model->getAttributes(), []);
        });
    }

    protected function writeAudit(string $action, array $oldValues, array $newValues): void
    {
        AuditLog::withoutEvents(function () use ($action, $oldValues, $newValues): void {
            AuditLog::create([
                'school_id' => $this->school_id ?? auth()->user()?->school_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'auditable_type' => static::class,
                'auditable_id' => $this->getKey(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
