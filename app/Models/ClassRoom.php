<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'academic_year_id', 'name', 'grade_level', 'capacity'];
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
    public function teacherAssignments(): HasMany { return $this->hasMany(TeacherAssignment::class); }
    public function attendanceSessions(): HasMany { return $this->hasMany(AttendanceSession::class); }
}
