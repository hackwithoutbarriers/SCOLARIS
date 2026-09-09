<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use App\Support\Academic\ReportCardData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ReportCard extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id', 'student_id', 'academic_year_id', 'term_id', 'template_id',
        'version', 'status', 'data', 'generated_by', 'generated_at',
    ];

    protected function casts(): array
    {
        return ['data' => 'array', 'generated_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $card): void {
            if ($card->exists && $card->getOriginal('status') === 'published' && $card->isDirty(['data', 'student_id', 'academic_year_id', 'term_id', 'template_id'])) {
                throw ValidationException::withMessages(['status' => 'Published report cards are immutable; create a revision instead.']);
            }
            if (! in_array($card->status, ['draft', 'review', 'approved', 'published', 'revision_requested'], true)) {
                throw ValidationException::withMessages(['status' => 'Le statut du bulletin n’est pas pris en charge.']);
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportCardTemplate::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReportCardVersion::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function normalizedData(): ?ReportCardData
    {
        return $this->data ? ReportCardData::fromArray($this->data) : null;
    }
}
