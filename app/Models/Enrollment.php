<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use App\Services\Payments\InvoiceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Enrollment extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'student_id', 'class_room_id', 'academic_year_id', 'enrollment_date', 'enrolled_at', 'status'];

    protected function casts(): array
    {
        return ['enrollment_date' => 'date', 'enrolled_at' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $enrollment): void {
            if (Term::isClosedForDate((int) $enrollment->school_id, (int) $enrollment->academic_year_id, $enrollment->enrolled_at ?? today())) {
                throw ValidationException::withMessages(['enrollment' => 'La période d’inscription est clôturée.']);
            }
        });
        static::updating(function (self $enrollment): void {
            $date = $enrollment->enrolled_at ?? $enrollment->getOriginal('enrolled_at') ?? today();
            if (Term::isClosedForDate((int) $enrollment->school_id, (int) $enrollment->academic_year_id, $date)) {
                throw ValidationException::withMessages(['enrollment' => 'La période d’inscription est clôturée.']);
            }
        });

        static::created(function (self $enrollment): void {
            if ($enrollment->status !== 'active') {
                return;
            }

            $structure = FeeStructure::query()
                ->where('school_id', $enrollment->school_id)
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->where('active', true)
                ->where(function ($query) use ($enrollment): void {
                    $query->where('class_room_id', $enrollment->class_room_id)
                        ->orWhereNull('class_room_id');
                })
                ->with('fees')
                ->orderByRaw('class_room_id IS NULL')
                ->first();

            if ($structure && $structure->fees->where('active', true)->isNotEmpty()) {
                app(InvoiceService::class)->generateForStudent($structure, $enrollment->student);
            }
        });
    }
}
