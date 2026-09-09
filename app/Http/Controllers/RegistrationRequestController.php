<?php

namespace App\Http\Controllers;

use App\Models\RegistrationRequest;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'requested_role' => ['required', 'in:director,teacher,accountant'],
            'school_id' => ['required_unless:requested_role,director', 'nullable', 'integer', 'exists:schools,id'],
            'school_name' => ['required_if:requested_role,director', 'nullable', 'string', 'max:255'],
            'school_code' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $duplicate = RegistrationRequest::query()
            ->where('email', strtolower($data['email']))
            ->where('status', RegistrationRequest::PENDING)
            ->exists();
        if ($duplicate) {
            return back()->with('status', 'Une demande est déjà en attente pour cette adresse.');
        }

        RegistrationRequest::create([
            ...$data,
            'email' => strtolower($data['email']),
            'password_hash' => Hash::make($data['password']),
            'status' => RegistrationRequest::PENDING,
        ]);

        return back()->with('status', 'Demande envoyée. Elle sera validée par le responsable compétent.');
    }
}
