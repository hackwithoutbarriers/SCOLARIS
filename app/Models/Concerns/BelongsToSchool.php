<?php

namespace App\Models\Concerns;

use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Support\Tenancy\SchoolContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function ($model): void {
            if (! $model->school_id && ($schoolId = app(SchoolContext::class)->id())) {
                $model->school_id = $schoolId;
            }
            if (auth()->check() && ! auth()->user()->isSuperAdmin() && (int) $model->school_id !== (int) auth()->user()->school_id) {
                throw new AuthorizationException('The record does not belong to the current school.');
            }
            if (auth()->check() && auth()->user()->isSuperAdmin() && auth()->user()->hasActiveSchoolIntervention()
                && ! auth()->user()->hasActiveSchoolIntervention((int) $model->school_id)) {
                throw new AuthorizationException('The record does not belong to the active intervention school.');
            }
        });
        static::updating(function ($model): void {
            if (auth()->check() && ! auth()->user()->isSuperAdmin() && (int) $model->school_id !== (int) auth()->user()->school_id) {
                throw new AuthorizationException('The record does not belong to the current school.');
            }
            if (auth()->check() && auth()->user()->isSuperAdmin() && auth()->user()->hasActiveSchoolIntervention()
                && ! auth()->user()->hasActiveSchoolIntervention((int) $model->school_id)) {
                throw new AuthorizationException('The record does not belong to the active intervention school.');
            }
            if ($model->isDirty('school_id') && $model->getOriginal('school_id') !== $model->school_id) {
                throw new AuthorizationException('A record cannot be moved between schools.');
            }
        });
        static::deleting(function ($model): void {
            if (auth()->check() && ! auth()->user()->isSuperAdmin() && (int) $model->school_id !== (int) auth()->user()->school_id) {
                throw new AuthorizationException('The record does not belong to the current school.');
            }
            if (auth()->check() && auth()->user()->isSuperAdmin() && auth()->user()->hasActiveSchoolIntervention()
                && ! auth()->user()->hasActiveSchoolIntervention((int) $model->school_id)) {
                throw new AuthorizationException('The record does not belong to the active intervention school.');
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->withoutGlobalScope(SchoolScope::class)->where(
            $query->getModel()->qualifyColumn('school_id'),
            $schoolId
        );
    }
}
