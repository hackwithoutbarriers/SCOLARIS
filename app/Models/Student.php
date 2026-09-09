<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'student_number', 'admission_number', 'first_name', 'last_name', 'middle_name', 'date_of_birth', 'gender', 'email', 'phone', 'address', 'status', 'photo_path', 'active'];
    protected function casts(): array { return ['date_of_birth' => 'date']; }
    protected $appends = ['full_name'];
    public function getFullNameAttribute(): string { return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]))); }
    protected static function booted(): void
    {
        static::creating(function (self $student): void {
            $student->student_number ??= $student->admission_number;
        });
    }
    public function guardians(): BelongsToMany { return $this->belongsToMany(Guardian::class, 'guardian_student')->withPivot('is_primary', 'receives_sms', 'receives_whatsapp')->withTimestamps(); }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
    public function grades(): HasMany { return $this->hasMany(Grade::class); }
    public function reportCards(): HasMany { return $this->hasMany(ReportCard::class); }
    public function appreciations(): HasMany { return $this->hasMany(Appreciation::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
