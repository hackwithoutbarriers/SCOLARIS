<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class School extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'slug', 'timezone', 'email', 'phone', 'address', 'city', 'country', 'logo_path', 'active', 'due_reminder_days', 'due_reminders_enabled', 'single_operator_mode', 'expense_categories', 'expense_approval_threshold', 'education_cycles', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name', 'mail_encryption', 'whatsapp_provider'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'due_reminder_days' => 'integer', 'due_reminders_enabled' => 'boolean', 'single_operator_mode' => 'boolean', 'expense_categories' => 'array', 'expense_approval_threshold' => 'integer', 'education_cycles' => 'array', 'mail_password' => 'encrypted', 'mail_port' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $school): void {
            $school->slug ??= str()->slug($school->name);
            $school->country ??= 'Togo';
        });
        static::created(function (self $school): void {
            foreach (ConductLabel::defaults() as $sortOrder => $label) {
                $school->conductLabels()->firstOrCreate(
                    ['label' => $label],
                    ['sort_order' => $sortOrder, 'active' => true],
                );
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function classRooms(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function subjectConfigs(): HasMany
    {
        return $this->hasMany(SubjectConfig::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function reportCardTemplates(): HasMany
    {
        return $this->hasMany(ReportCardTemplate::class);
    }

    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function conductLabels(): HasMany
    {
        return $this->hasMany(ConductLabel::class);
    }

    public function mentionThresholds(): HasMany
    {
        return $this->hasMany(MentionThreshold::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SchoolSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(SchoolSubscription::class)->whereIn('status', ['trial', 'active'])->latestOfMany();
    }

    public function enabledEducationCycles(): array
    {
        return array_values(array_intersect(
            array_keys(self::educationCycleOptions()),
            $this->education_cycles ?: array_keys(self::educationCycleOptions()),
        ));
    }

    public static function educationCycleOptions(): array
    {
        return [
            'primaire' => 'Primaire',
            'college' => 'Collège',
            'lycee' => 'Lycée',
        ];
    }

    public static function educationLevelOptions(): array
    {
        return [
            'primaire' => [
                'ci' => 'CI',
                'cp' => 'CP',
                'ce1' => 'CE1',
                'ce2' => 'CE2',
                'cm1' => 'CM1',
                'cm2' => 'CM2',
            ],
            'college' => [
                '6e' => '6e',
                '5e' => '5e',
                '4e' => '4e',
                '3e' => '3e',
            ],
            'lycee' => [
                'seconde' => 'Seconde',
                'premiere' => 'Première',
                'terminale' => 'Terminale',
            ],
        ];
    }

    public static function educationSeriesOptions(): array
    {
        return [
            'generale' => 'Générale',
            'technique' => 'Technique',
        ];
    }
}
