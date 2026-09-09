<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Assessment extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id', 'subject_config_id', 'term_id', 'teacher_id', 'title',
        'assessment_date', 'max_score', 'weight', 'status',
    ];

    protected function casts(): array
    {
        return ['assessment_date' => 'date', 'max_score' => 'decimal:2', 'weight' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $assessment): void {
            if ((float) $assessment->max_score <= 0 || (float) $assessment->weight < 0) {
                throw ValidationException::withMessages(['max_score' => 'Le barème maximal doit être positif et le coefficient ne peut pas être négatif.']);
            }
        });
    }

    public function subjectConfig(): BelongsTo
    {
        return $this->belongsTo(SubjectConfig::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
