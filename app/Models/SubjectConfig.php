<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class SubjectConfig extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id', 'subject_id', 'academic_year_id', 'class_room_id', 'version',
        'grading_method', 'passing_score', 'max_score', 'weight', 'rules', 'active',
    ];

    protected function casts(): array
    {
        return ['passing_score' => 'decimal:2', 'max_score' => 'decimal:2', 'weight' => 'decimal:2', 'rules' => 'array', 'active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $config): void {
            if ((float) $config->max_score <= 0 || (float) $config->passing_score < 0 || (float) $config->passing_score > 100) {
                throw ValidationException::withMessages(['passing_score' => 'Le seuil de réussite doit être compris entre 0 et 100 et le barème maximal doit être positif.']);
            }
            if (! in_array($config->grading_method, ['weighted_average', 'simple_average'], true)) {
                throw ValidationException::withMessages(['grading_method' => 'Cette méthode de notation n’est pas prise en charge.']);
            }
            foreach ($config->gradeBands() as $band) {
                if (! isset($band['min']) || ! is_numeric($band['min'])) {
                    throw ValidationException::withMessages(['rules' => 'Each grade band requires a numeric min value.']);
                }
            }
        });
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function gradeBands(): array
    {
        $rules = $this->rules['grade_bands'] ?? $this->rules ?? [];

        return is_array($rules) ? array_values($rules) : [];
    }
}
