<?php

namespace App\Services;

use App\Models\RegistrationRequest;
use App\Models\School;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegistrationApprovalService
{
    public function approve(RegistrationRequest $request, User $reviewer): User|StaffInvitation
    {
        if ($request->status !== RegistrationRequest::PENDING) {
            throw ValidationException::withMessages(['status' => 'Cette demande a déjà été traitée.']);
        }

        $isDirectorRequest = $request->requested_role === 'director';
        if ($isDirectorRequest && ! $reviewer->isSuperAdmin()) {
            throw new AuthorizationException;
        }
        $isNewSchoolOnboarding = $isDirectorRequest
            && $request->request_type === 'new_school'
            && ! $request->school_id;
        if ($reviewer->isSuperAdmin() && ! $isNewSchoolOnboarding) {
            abort_unless(
                $request->school_id && $reviewer->hasActiveSchoolIntervention((int) $request->school_id),
                403,
                'Ouvrez une intervention sur cette école avant de valider la demande.',
            );
        }
        if (! $isDirectorRequest && ! $reviewer->isSuperAdmin() && (! $reviewer->isDirector() || $request->school_id !== $reviewer->school_id)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($request, $reviewer, $isDirectorRequest): User|StaffInvitation {
            $school = $request->school;
            if (! $isDirectorRequest && (! $school || ($request->school_code && strcasecmp($school->code, trim($request->school_code)) !== 0))) {
                throw ValidationException::withMessages(['school_code' => 'Le code école ne correspond pas à l’école demandée.']);
            }
            $createdSchoolForOnboarding = false;
            if ($isDirectorRequest && ! $school && $request->request_type === 'new_school') {
                $school = School::create([
                    'name' => $request->school_name,
                    'code' => $request->school_code ?: strtoupper(substr(str_replace(' ', '', $request->school_name), 0, 8)),
                    'slug' => str()->slug($request->school_name),
                    'country' => 'Togo',
                    'city' => $request->city,
                    'active' => true,
                ]);
                $createdSchoolForOnboarding = true;
            }

            if ($isDirectorRequest && (! $request->password_hash || ! $request->email)) {
                [$invitation] = app(StaffInvitationService::class)->create($reviewer, [
                    'school_id' => $school?->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'role' => 'director',
                    'preferred_channel' => $request->preferred_channel,
                ], platformOnboarding: $createdSchoolForOnboarding);
                $request->update([
                    'school_id' => $school?->id,
                    'status' => RegistrationRequest::APPROVED,
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                ]);

                return $invitation;
            }

            $email = mb_strtolower($request->email);
            $phone = filled($request->phone) ? PhoneNumberFormatter::toE164($request->phone) : null;
            $users = User::withoutGlobalScopes();
            $existingByEmail = (clone $users)->where('email', $email)->first();
            $existingByPhone = $phone ? (clone $users)->where('login_phone', $phone)->first() : null;

            if ($existingByEmail && $existingByPhone && $existingByEmail->isNot($existingByPhone)) {
                throw ValidationException::withMessages(['email' => 'L’e-mail et le téléphone sont déjà associés à deux comptes différents.']);
            }

            $existingUser = $existingByEmail ?? $existingByPhone;
            if ($existingUser && (int) $existingUser->school_id !== (int) $school?->id) {
                throw ValidationException::withMessages(['email' => 'Cet identifiant est déjà associé à un compte d’une autre école.']);
            }
            if ($existingByPhone && filled($existingByPhone->email) && mb_strtolower($existingByPhone->email) !== $email) {
                throw ValidationException::withMessages(['phone' => 'Ce téléphone est déjà associé à une autre adresse de compte.']);
            }

            $user = $existingUser ?? new User;
            $user->fill([
                'name' => $request->name,
                'first_name' => $request->name,
                'email' => $email,
                'phone' => $phone ?? $existingUser?->phone,
                'school_id' => $school?->id,
                'role' => $request->requested_role,
                'password' => $request->password_hash,
                'is_active' => true,
                'must_change_password' => ! $isDirectorRequest,
                'email_verified_at' => now(),
            ]);
            $user->save();

            $request->update([
                'school_id' => $school?->id,
                'status' => RegistrationRequest::APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            if ($reviewer->isSuperAdmin() && $createdSchoolForOnboarding && $school) {
                app(SuperAdminInterventionService::class)->record($school, 'platform_director_account_created', [
                    'role' => 'director',
                ]);
            }

            return $user;
        });
    }

    public function reject(RegistrationRequest $request, User $reviewer, string $reason): void
    {
        if ($request->status !== RegistrationRequest::PENDING) {
            throw ValidationException::withMessages(['status' => 'Cette demande a déjà été traitée.']);
        }
        if (! $reviewer->isSuperAdmin() && (! $reviewer->isDirector() || $request->school_id !== $reviewer->school_id || $request->requested_role === 'director')) {
            throw new AuthorizationException;
        }
        $request->update(['status' => RegistrationRequest::REJECTED, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'rejection_reason' => $reason]);
    }
}
