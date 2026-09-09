<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appreciation extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    protected $fillable = ['school_id', 'student_id', 'academic_year_id', 'term_id', 'average_score', 'rank', 'label', 'comment', 'metadata'];
    protected function casts(): array { return ['average_score' => 'decimal:2', 'metadata' => 'array']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
}
