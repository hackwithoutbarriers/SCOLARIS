<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\PhoneNumberFormatter;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $identifier = trim((string) $data['identifier']);
        $remember = (bool) ($data['remember'] ?? false);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            if (! Filament::auth()->attempt([
                'email' => mb_strtolower($identifier),
                'password' => $data['password'],
            ], $remember)) {
                $this->throwFailureValidationException();
            }

            $user = Filament::auth()->user();
        } else {
            $phone = PhoneNumberFormatter::toE164($identifier);
            $user = $phone ? User::query()->where('login_phone', $phone)->first() : null;
            if (! $user || ! Hash::check($data['password'], $user->password)) {
                $this->throwFailureValidationException();
            }

            Filament::auth()->login($user, $remember);
        }

        if (($user instanceof FilamentUser) && ! $user->canAccessPanel(Filament::getCurrentPanel())) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('identifier')
            ->label('Adresse e-mail ou téléphone')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.identifier' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
