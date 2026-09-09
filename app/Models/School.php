<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'slug', 'timezone', 'email', 'phone', 'address', 'city', 'country', 'logo_path', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    protected static function booted(): void
    {
        static::creating(function (self $school): void {
            $school->slug ??= str()->slug($school->name);
            $school->country ??= 'Togo';
        });
    }

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function academicYears(): HasMany { return $this->hasMany(AcademicYear::class); }
    public function classRooms(): HasMany { return $this->hasMany(ClassRoom::class); }
    public function subjects(): HasMany { return $this->hasMany(Subject::class); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function guardians(): HasMany { return $this->hasMany(Guardian::class); }
    public function subjectConfigs(): HasMany { return $this->hasMany(SubjectConfig::class); }
    public function assessments(): HasMany { return $this->hasMany(Assessment::class); }
    public function grades(): HasMany { return $this->hasMany(Grade::class); }
    public function reportCardTemplates(): HasMany { return $this->hasMany(ReportCardTemplate::class); }
    public function reportCards(): HasMany { return $this->hasMany(ReportCard::class); }
}
