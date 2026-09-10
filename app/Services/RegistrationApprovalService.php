<?php

namespace App\Services;

use App\Models\RegistrationRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegistrationApprovalService
{
    public function approve(RegistrationRequest $request, User $reviewer): User
    {
        if ($request->status !== RegistrationRequest::PENDING) {
            throw ValidationException::withMessages(['status' => 'Cette demande a déjà été traitée.']);
        }

        $isDirectorRequest = $request->requested_role === 'director';
        if ($isDirectorRequest && !$reviewer->isSuperAdmin()) {
            throw new AuthorizationException();
        }
        if (!$isDirectorRequest && !$reviewer->isSuperAdmin() && (!$reviewer->isDirector() || $request->school_id !== $reviewer->school_id)) {
            throw new AuthorizationException();
        }

        return DB::transaction(function () use ($request, $reviewer, $isDirectorRequest): User {
            $school = $request->school;
            if (!$isDirectorRequest && (!$school || ($request->school_code && strcasecmp($school->code, trim($request->school_code)) !== 0))) {
                throw ValidationException::withMessages(['school_code' => 'Le code école ne correspond pas à l’école demandée.']);
            }
            if ($isDirectorRequest && !$school) {
                $school = School::create([
                    'name' => $request->school_name,
                    'code' => $request->school_code ?: strtoupper(substr(str_replace(' ', '', $request->school_name), 0, 8)),
                    'slug' => str()->slug($request->school_name),
                    'country' => 'Togo',
                    'active' => true,
                ]);
            }

            $user = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => strtolower($request->email)],
                [
                    'name' => $request->name,
                    'first_name' => $request->name,
                    'phone' => $request->phone,
                    'school_id' => $school?->id,
                    'role' => $request->requested_role,
                    'password' => $request->password_hash,
                    'is_active' => true,
                    'must_change_password' => !$isDirectorRequest,
                    'email_verified_at' => now(),
                ],
            );

            $request->update([
                'school_id' => $school?->id,
                'status' => RegistrationRequest::APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $user;
        });
    }

    public function reject(RegistrationRequest $request, User $reviewer, string $reason): void
    {
        if ($request->status !== RegistrationRequest::PENDING) {
            throw ValidationException::withMessages(['status' => 'Cette demande a déjà été traitée.']);
        }
        if (!$reviewer->isSuperAdmin() && (!$reviewer->isDirector() || $request->school_id !== $reviewer->school_id || $request->requested_role === 'director')) {
            throw new AuthorizationException();
        }
        $request->update(['status' => RegistrationRequest::REJECTED, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'rejection_reason' => $reason]);
    }
}
