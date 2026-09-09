<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'name', 'code', 'description'];
    public function teacherAssignments(): HasMany { return $this->hasMany(TeacherAssignment::class); }
    public function configs(): HasMany { return $this->hasMany(SubjectConfig::class); }
}
