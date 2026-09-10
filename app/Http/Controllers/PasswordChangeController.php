<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function create(): View
    {
        return view('auth.change-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->uncompromised()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        return redirect('/admin');
    }
}
