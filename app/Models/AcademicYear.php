<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    protected $fillable = ['school_id', 'name', 'start_date', 'end_date', 'starts_at', 'ends_at', 'status', 'is_current'];
    protected function casts(): array { return ['start_date' => 'date', 'end_date' => 'date', 'starts_at' => 'date', 'ends_at' => 'date', 'is_current' => 'boolean']; }
    public function isActive(): bool { return $this->status === 'active' || $this->is_current; }
    protected static function booted(): void
    {
        static::creating(function (self $year): void {
            $year->start_date ??= $year->starts_at;
            $year->end_date ??= $year->ends_at;
        });
    }
    public function terms(): HasMany { return $this->hasMany(Term::class); }
    public function classRooms(): HasMany { return $this->hasMany(ClassRoom::class); }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
    public function subjectConfigs(): HasMany { return $this->hasMany(SubjectConfig::class); }
    public function reportCards(): HasMany { return $this->hasMany(ReportCard::class); }
}
