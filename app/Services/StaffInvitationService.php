<?php

namespace App\Services;

use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class StaffInvitationService
{
    private const ROLES = ['teacher', 'accountant', 'secretary'];

    public function create(User $creator, array $data): array
    {
        if (!$creator->isSuperAdmin() && !$creator->isDirector()) {
            throw new AuthorizationException();
        }

        $schoolId = $creator->isSuperAdmin() ? (int) $data['school_id'] : $creator->school_id;
        if (!$schoolId) {
            throw ValidationException::withMessages(['school_id' => 'Une école est obligatoire.']);
        }

        $role = (string) $data['role'];
        $secondaryRole = $data['secondary_role'] ?? null;
        if (!in_array($role, self::ROLES, true)
            || ($secondaryRole !== null && !in_array($secondaryRole, self::ROLES, true))
            || $role === $secondaryRole) {
            throw ValidationException::withMessages(['role' => 'Les rôles sélectionnés sont invalides.']);
        }

        $rawToken = Str::random(64);
        $invitation = StaffInvitation::create([
            'school_id' => $schoolId,
            'created_by' => $creator->id,
            'email' => strtolower(trim($data['email'])),
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'role' => $role,
            'secondary_role' => $secondaryRole,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays(2),
        ]);

        try {
            app(NotificationService::class)->queueInvitation($invitation, $rawToken);
        } catch (\Throwable $exception) {
            $invitation->delete();
            throw $exception;
        }

        return [$invitation, $rawToken];
    }

    public function accept(string $rawToken, string $password): User
    {
        $invitation = StaffInvitation::query()
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();

        if (!$invitation || $invitation->accepted_at || $invitation->expires_at->isPast()) {
            throw ValidationException::withMessages(['token' => 'Cette invitation est invalide ou expirée.']);
        }

        $user = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $invitation->email],
            [
                'name' => $invitation->name,
                'first_name' => $invitation->name,
                'phone' => $invitation->phone,
                'school_id' => $invitation->school_id,
                'role' => $invitation->role,
                'secondary_role' => $invitation->secondary_role,
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ],
        );

        $invitation->update(['accepted_at' => now()]);

        return $user;
    }
}
