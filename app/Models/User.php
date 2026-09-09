<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\BelongsToSchool;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'is_active',
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
            'onboarding_step' => 'integer',
            'onboarding_completed_at' => 'datetime',
        ];
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
        return $this->is_active && in_array($this->role, ['director', 'admin', 'principal'], true);
    }

    public function isAdmin(): bool
    {
        return $this->is_active && ($this->isSuperAdmin() || $this->isDirector());
    }

    public function isFinanceOperator(): bool
    {
        return $this->is_active && ($this->isSuperAdmin() || $this->isDirector() || $this->role === 'accountant');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $this->email_verified_at !== null
            && ($this->isSuperAdmin() || $this->school_id !== null);
    }
}
