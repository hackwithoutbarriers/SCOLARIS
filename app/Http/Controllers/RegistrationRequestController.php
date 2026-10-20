<?php

namespace App\Http\Controllers;

use App\Models\RegistrationRequest;
use App\Models\School;
use App\Services\PhoneNumberFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrationRequestController extends Controller
{
    public function create(): View
    {
        return view('auth.register-request');
    }

    public function store(Request $request): RedirectResponse
    {
        $key = 'registration:'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429);
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'request_type' => ['sometimes', 'in:new_school,existing_school'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:30', 'required_without:email'],
            'requested_role' => ['required', 'in:director'],
            'school_id' => ['prohibited', 'nullable'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_code' => ['nullable', 'string', 'max:50', 'required_if:request_type,existing_school'],
            'city' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'preferred_channel' => ['nullable', 'in:email,whatsapp'],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);
        if (filled($data['phone'] ?? null)) {
            $phone = PhoneNumberFormatter::toE164($data['phone']);
            if (! $phone) {
                return back()->withInput()->withErrors(['phone' => 'Saisissez un numéro togolais valide, par exemple +22890000000.']);
            }
            $data['phone'] = $phone;
        }
        $data['request_type'] ??= 'new_school';
        if ($data['request_type'] === 'existing_school') {
            $school = School::query()->where('code', $data['school_code'])->first();
            if (! $school) {
                return back()->withInput()->withErrors(['school_code' => 'Aucune école ne correspond à ce code.']);
            }
            $data['school_id'] = $school->id;
        }

        $duplicate = RegistrationRequest::query()
            ->when($data['email'] ?? null, fn ($query, $email) => $query->where('email', strtolower($email)))
            ->when(! ($data['email'] ?? null), fn ($query) => $query->where('phone', $data['phone']))
            ->where('status', RegistrationRequest::PENDING)
            ->exists();
        if ($duplicate) {
            return back()->with('status', 'Une demande est déjà en attente pour cette adresse.');
        }

        RegistrationRequest::create([
            ...$data,
            'email' => isset($data['email']) ? strtolower($data['email']) : null,
            'password_hash' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null,
            'status' => RegistrationRequest::PENDING,
        ]);

        return back()->with('status', 'Demande envoyée. Elle sera validée par le responsable compétent.');
    }
}
