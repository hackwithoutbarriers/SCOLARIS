<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'academic_year_id', 'name', 'starts_at', 'ends_at', 'sort_order', 'status', 'grade_validation_status', 'closed_at', 'closed_by', 'grades_validated_at', 'grades_validated_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'closed_at' => 'datetime', 'grades_validated_at' => 'datetime'];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function gradesAreValidated(): bool
    {
        return $this->grade_validation_status === 'validated';
    }

    public static function isClosedForDate(int $schoolId, int $academicYearId, mixed $date): bool
    {
        return static::query()->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'closed')
            ->whereDate('starts_at', '<=', $date)
            ->whereDate('ends_at', '>=', $date)
            ->exists();
    }
}
