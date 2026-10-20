<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Student;

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
        if ($this instanceof Student || $this instanceof Guardian) {
            $safeFields = ['school_id', 'status', 'active'];
            $oldValues = array_intersect_key($oldValues, array_flip($safeFields));
            $newValues = array_intersect_key($newValues, array_flip($safeFields));
        }

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
