<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Grade extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    protected $fillable = [
        'school_id', 'assessment_id', 'student_id', 'graded_by', 'score',
        'normalized_score', 'grade_letter', 'remarks', 'validated_at',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'normalized_score' => 'decimal:6', 'validated_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $grade): void {
            $assessment = $grade->relationLoaded('assessment')
                ? $grade->assessment
                : Assessment::withoutGlobalScopes()->find($grade->assessment_id);
            if (!$assessment) {
                throw ValidationException::withMessages(['assessment_id' => 'The assessment does not exist.']);
            }
            $grade->score = self::validateScore($grade->score, $assessment->max_score);
            $grade->normalized_score ??= ((float) $grade->score / (float) $assessment->max_score) * 100;
        });
    }

    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function grader(): BelongsTo { return $this->belongsTo(User::class, 'graded_by'); }

    public static function validateScore(float|int|string $score, float|int|string $maxScore): float
    {
        $score = (float) $score;
        $maxScore = (float) $maxScore;
        if ($maxScore <= 0 || $score < 0 || $score > $maxScore) {
            throw ValidationException::withMessages(['score' => 'The score must be between 0 and the assessment maximum.']);
        }
        return $score;
    }
}
