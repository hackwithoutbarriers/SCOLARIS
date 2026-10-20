<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class MentionThreshold extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id', 'academic_year_id', 'label', 'minimum_score',
        'maximum_score', 'sort_order', 'active',
    ];

    protected function casts(): array
    {
        return ['minimum_score' => 'decimal:2', 'maximum_score' => 'decimal:2', 'active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $threshold): void {
            $minimum = (float) $threshold->minimum_score;
            $maximum = $threshold->maximum_score === null ? null : (float) $threshold->maximum_score;
            if ($minimum < 0 || $minimum > 100 || ($maximum !== null && ($maximum < 0 || $maximum > 100 || $maximum <= $minimum))) {
                throw ValidationException::withMessages(['minimum_score' => 'Les bornes de mention doivent être comprises entre 0 et 100 et ordonnées.']);
            }
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function appliesTo(float $score): bool
    {
        return $score >= (float) $this->minimum_score
            && ($this->maximum_score === null || $score < (float) $this->maximum_score);
    }
}
