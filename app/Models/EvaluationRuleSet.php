<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class EvaluationRuleSet extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    protected $fillable = [
        'school_id', 'academic_year_id', 'class_room_id', 'version', 'name',
        'grading_system', 'maximum_score', 'rounding', 'average_method', 'rules', 'active',
    ];

    protected function casts(): array
    {
        return ['maximum_score' => 'decimal:6', 'rules' => 'array', 'active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $ruleSet): void {
            if ((float) $ruleSet->maximum_score <= 0 || !in_array($ruleSet->rounding, ['presentation_only', 'half_up', 'down', 'up'], true)) {
                throw ValidationException::withMessages(['maximum_score' => 'Rule set maximum and rounding policy are invalid.']);
            }
            if (!in_array($ruleSet->average_method, ['weighted_average', 'simple_average'], true)) {
                throw ValidationException::withMessages(['average_method' => 'Unsupported average method.']);
            }
        });
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class); }
}
