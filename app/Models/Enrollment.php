<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'student_id', 'class_room_id', 'academic_year_id', 'enrollment_date', 'enrolled_at', 'status'];
    protected function casts(): array { return ['enrollment_date' => 'date', 'enrolled_at' => 'date']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
}
