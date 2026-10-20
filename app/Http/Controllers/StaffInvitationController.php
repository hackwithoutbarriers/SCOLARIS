<?php

namespace App\Http\Controllers;

use App\Services\StaffInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffInvitationController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.accept-invitation', ['token' => $token]);
    }

    public function store(Request $request, string $token, StaffInvitationService $service): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->uncompromised()],
        ]);

        $service->accept($token, $data['password']);

        return redirect('/admin/login')->with('status', 'Votre compte est activé. Vous pouvez maintenant vous connecter.');
    }
}
