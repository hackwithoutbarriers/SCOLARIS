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
    protected $fillable = ['school_id', 'student_number', 'admission_number', 'card_token', 'first_name', 'last_name', 'middle_name', 'date_of_birth', 'gender', 'email', 'phone', 'address', 'status', 'photo_path', 'active'];
    protected function casts(): array { return ['date_of_birth' => 'date']; }
    protected $appends = ['full_name'];
    public function getFullNameAttribute(): string { return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]))); }
    protected static function booted(): void
    {
        static::creating(function (self $student): void {
            if ($student->student_number ?? $student->admission_number) {
                $student->student_number ??= $student->admission_number;
                return;
            }
            ReceiptSequence::query()->insertOrIgnore([
                'school_id' => $student->school_id, 'sequence_type' => 'student',
                'next_number' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $sequence = ReceiptSequence::query()->where('school_id', $student->school_id)
                ->where('sequence_type', 'student')->lockForUpdate()->firstOrFail();
            $number = (int) $sequence->next_number;
            $sequence->increment('next_number');
            $student->student_number = 'STU-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
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
