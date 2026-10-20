<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAssignment extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'teacher_id', 'class_room_id', 'subject_id', 'academic_year_id'];
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
}
