<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    use BelongsToSchool, Auditable;

    protected $fillable = ['school_id', 'academic_year_id', 'class_room_id', 'name', 'description', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class); }
    public function fees(): HasMany { return $this->hasMany(Fee::class); }
}
