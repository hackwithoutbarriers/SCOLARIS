<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PragmaRX\Google2FA\Google2FA;

#[Fillable(['name', 'email', 'password', 'status', 'is_superuser', 'approved_by_user_id', 'approved_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements FilamentUser
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_superuser' => 'boolean',
            'approved_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by_user_id');
    }

    public function isApproved(): bool
    {
        return $this->is_superuser || $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_superuser || $this->status === self::STATUS_APPROVED;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && filled($this->two_factor_confirmed_at);
    }

    public function verifyTwoFactorCode(string $code): bool
    {
        if (! filled($this->two_factor_secret)) {
            return false;
        }

        return app(Google2FA::class)->verifyKey($this->two_factor_secret, trim($code), 8);
    }
}
