<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\BelongsToSchool;
use App\Services\PhoneNumberFormatter;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use BelongsToSchool, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'first_name', 'last_name',
        'email',
        'phone',
        'password',
        'school_id',
        'role',
        'secondary_role',
        'is_active',
        'must_change_password',
        'email_verified_at',
        'onboarding_step',
        'onboarding_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'onboarding_step' => 'integer',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): array => [
                'phone' => $value,
                'login_phone' => $value ? PhoneNumberFormatter::toE164($value) : null,
            ],
        );
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'teacher_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_active && $this->role === 'super_admin';
    }

    public function isDirector(): bool
    {
        return $this->is_active
            && $this->role !== 'super_admin'
            && $this->hasRole(['director', 'admin', 'principal']);
    }

    public function isCenseur(): bool
    {
        return $this->is_active && $this->role !== 'super_admin' && $this->hasRole('censeur');
    }

    public function isAttendanceManager(): bool
    {
        if ($this->isSuperAdmin()) {
            return $this->hasActiveSchoolIntervention();
        }

        return $this->isDirector() || $this->isCenseur();
    }

    public function isAdmin(): bool
    {
        return $this->is_active
            && ($this->isDirector() || ($this->isSuperAdmin() && $this->hasActiveSchoolIntervention()));
    }

    public function isFinanceOperator(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return $this->hasActiveSchoolIntervention();
        }

        return $this->isDirector() || $this->hasRole('accountant');
    }

    public function isSecretary(): bool
    {
        return $this->is_active && $this->role !== 'super_admin' && $this->hasRole('secretary');
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return in_array($this->role, $roles, true)
            || in_array($this->secondary_role, $roles, true);
    }

    public function hasActiveSchoolIntervention(?int $schoolId = null): bool
    {
        if (! $this->isSuperAdmin()) {
            return false;
        }

        $interventionSchoolId = (int) session()->get('scolaris_intervention.school_id', 0);
        $expiresAt = (int) session()->get('scolaris_intervention.expires_at', 0);

        return $interventionSchoolId > 0
            && $expiresAt > now()->timestamp
            && ($schoolId === null || $interventionSchoolId === $schoolId);
    }

    public function canAccessSchool(?int $schoolId): bool
    {
        if (! $this->is_active || ! $schoolId) {
            return false;
        }

        return $this->isSuperAdmin()
            ? $this->hasActiveSchoolIntervention($schoolId)
            : (int) $this->school_id === $schoolId;
    }

    public function canUseSchoolIntervention(mixed ...$targets): bool
    {
        if (! $this->hasActiveSchoolIntervention()) {
            return false;
        }

        foreach ($targets as $target) {
            if ($target instanceof Model && $target->getAttribute('school_id') !== null
                && ! $this->hasActiveSchoolIntervention((int) $target->getAttribute('school_id'))) {
                return false;
            }
        }

        return true;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $this->email_verified_at !== null
            && ($this->isSuperAdmin() || $this->school_id !== null);
    }
}
