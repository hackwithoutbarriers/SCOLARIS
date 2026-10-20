<?php

namespace App\Services;

use App\Models\School;
use App\Models\StaffInvitation;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class StaffInvitationService
{
    private const ROLES = ['director', 'teacher', 'accountant', 'secretary', 'censeur'];

    public function create(User $creator, array $data, bool $platformOnboarding = false): array
    {
        if (! $creator->isSuperAdmin() && ! $creator->isDirector()) {
            throw new AuthorizationException;
        }

        $schoolId = $creator->isSuperAdmin() ? (int) $data['school_id'] : $creator->school_id;
        if (! $schoolId) {
            throw ValidationException::withMessages(['school_id' => 'Une école est obligatoire.']);
        }
        if ($creator->isSuperAdmin()) {
            if ($platformOnboarding) {
                if (($data['role'] ?? null) !== 'director') {
                    throw new AuthorizationException;
                }
            } else {
                abort_unless($creator->hasActiveSchoolIntervention($schoolId), 403, 'Ouvrez une intervention sur cette école avant d’inviter un membre.');
            }
        }

        $email = filled($data['email'] ?? null) ? strtolower(trim($data['email'])) : null;
        $phone = filled($data['phone'] ?? null) ? PhoneNumberFormatter::toE164($data['phone']) : null;
        if (! $email && ! $phone) {
            throw ValidationException::withMessages(['email' => 'Un e-mail ou un numéro WhatsApp est obligatoire.']);
        }
        if (filled($data['phone'] ?? null) && ! $phone) {
            throw ValidationException::withMessages(['phone' => 'Saisissez un numéro togolais valide, par exemple +22890000000.']);
        }

        $role = (string) $data['role'];
        $secondaryRole = $data['secondary_role'] ?? null;
        if (! in_array($role, self::ROLES, true)
            || ($secondaryRole !== null && ! in_array($secondaryRole, self::ROLES, true))
            || $role === $secondaryRole) {
            throw ValidationException::withMessages(['role' => 'Les rôles sélectionnés sont invalides.']);
        }

        return DB::transaction(function () use ($creator, $data, $schoolId, $email, $phone, $role, $secondaryRole, $platformOnboarding): array {
            $rawToken = Str::random(64);
            $invitation = StaffInvitation::create([
                'school_id' => $schoolId,
                'created_by' => $creator->id,
                'email' => $email,
                'name' => $data['name'],
                'phone' => $phone,
                'role' => $role,
                'secondary_role' => $secondaryRole,
                'preferred_channel' => $data['preferred_channel'] ?? null,
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => now()->addDays(2),
            ]);

            app(NotificationService::class)->queueInvitation($invitation, $rawToken);

            if ($creator->isSuperAdmin()) {
                app(SuperAdminInterventionService::class)->record(
                    School::query()->findOrFail($schoolId),
                    $platformOnboarding ? 'platform_director_invitation_created' : 'staff_invitation_created',
                    ['role' => $role],
                );
            }

            return [$invitation, $rawToken];
        });
    }

    public function accept(string $rawToken, string $password): User
    {
        return DB::transaction(function () use ($rawToken, $password): User {
            $invitation = StaffInvitation::query()
                ->where('token_hash', hash('sha256', $rawToken))
                ->lockForUpdate()
                ->first();

            if (! $invitation || $invitation->accepted_at || $invitation->expires_at->isPast()) {
                throw ValidationException::withMessages(['token' => 'Cette invitation est invalide ou expirée.']);
            }

            $email = filled($invitation->email) ? strtolower(trim($invitation->email)) : null;
            $phone = filled($invitation->phone) ? PhoneNumberFormatter::toE164($invitation->phone) : null;
            if (! $email && ! $phone) {
                throw ValidationException::withMessages(['token' => 'Cette invitation ne contient aucun identifiant de connexion valide.']);
            }

            $users = User::withoutGlobalScopes();
            $existingByEmail = $email ? (clone $users)->where('email', $email)->first() : null;
            $existingByPhone = $phone ? (clone $users)->where('login_phone', $phone)->first() : null;
            if ($existingByEmail && $existingByPhone && $existingByEmail->isNot($existingByPhone)) {
                throw ValidationException::withMessages(['email' => 'L’e-mail et le téléphone sont déjà associés à deux comptes différents.']);
            }

            $existingUser = $existingByEmail ?? $existingByPhone;
            if ($existingUser && (int) $existingUser->school_id !== (int) $invitation->school_id) {
                throw ValidationException::withMessages([
                    'email' => 'Cet identifiant est déjà associé à un compte d’une autre école.',
                ]);
            }
            if ($existingByPhone && $email && filled($existingByPhone->email) && mb_strtolower($existingByPhone->email) !== $email) {
                throw ValidationException::withMessages(['phone' => 'Ce téléphone est déjà associé à une autre adresse de compte.']);
            }

            $user = $existingUser ?? new User;
            $user->fill([
                'name' => $invitation->name,
                'first_name' => $invitation->name,
                'email' => $email ?? $existingUser?->email,
                'phone' => $phone ?? $existingUser?->phone,
                'school_id' => $invitation->school_id,
                'role' => $invitation->role,
                'secondary_role' => $invitation->secondary_role,
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]);
            $user->save();

            AuditLog::create([
                'school_id' => $invitation->school_id,
                'user_id' => $invitation->created_by,
                'action' => 'user.invitation_accepted',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'old_values' => $existingUser ? [
                    'role' => $existingUser->getOriginal('role'),
                    'secondary_role' => $existingUser->getOriginal('secondary_role'),
                    'is_active' => $existingUser->getOriginal('is_active'),
                ] : [],
                'new_values' => [
                    'role' => $user->role,
                    'secondary_role' => $user->secondary_role,
                    'is_active' => $user->is_active,
                    'invitation_id' => $invitation->id,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });
    }
}
