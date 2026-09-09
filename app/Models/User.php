<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToSchool;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, BelongsToSchool;

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
        ];
    }

    public function teacherAssignments(): HasMany { return $this->hasMany(TeacherAssignment::class, 'teacher_id'); }
    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isDirector(): bool { return in_array($this->role, ['director', 'admin', 'principal'], true); }
    public function isAdmin(): bool { return $this->isSuperAdmin() || $this->isDirector(); }
    public function isFinanceOperator(): bool { return $this->isSuperAdmin() || $this->isDirector() || $this->role === 'accountant'; }
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $this->email_verified_at !== null
            && ($this->isSuperAdmin() || $this->school_id !== null);
    }
}
