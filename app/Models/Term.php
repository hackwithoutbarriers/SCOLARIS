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
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'academic_year_id', 'name', 'starts_at', 'ends_at', 'sort_order'];
    protected function casts(): array { return ['starts_at' => 'date', 'ends_at' => 'date']; }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function assessments(): HasMany { return $this->hasMany(Assessment::class); }
    public function reportCards(): HasMany { return $this->hasMany(ReportCard::class); }
}
