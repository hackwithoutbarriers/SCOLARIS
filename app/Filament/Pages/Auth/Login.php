<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public bool $requiresTwoFactor = false;

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        $user = User::where('email', $data['email'] ?? '')->first();

        if ($user && $user->status === User::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'data.email' => 'Votre demande d’inscription est en attente de validation par le super administrateur.',
            ]);
        }

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        if ($user instanceof User && $user->hasTwoFactorEnabled()) {
            $code = trim((string) ($data['two_factor_code'] ?? ''));

            if (! $user->verifyTwoFactorCode($code)) {
                $this->requiresTwoFactor = true;
                Filament::auth()->logout();
                session()->regenerateToken();

                throw ValidationException::withMessages([
                    'data.two_factor_code' => 'Le code de vérification 2FA est requis ou invalide.',
                ]);
            }
        }

        $this->requiresTwoFactor = false;
        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                TextInput::make('two_factor_code')
                    ->label('Code de vérification 2FA')
                    ->numeric()
                    ->length(6)
                    ->prefixIcon('heroicon-o-shield-check')
                    ->autocomplete('one-time-code')
                    ->helperText('Saisissez le code généré par votre application d’authentification.')
                    ->visible(fn (): bool => $this->requiresTwoFactor)
                    ->extraInputAttributes(['inputmode' => 'numeric']),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label('Adresse e-mail')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus();
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label('Mot de passe')
            ->password()
            ->revealable(false)
            ->required()
            ->autocomplete('current-password');
    }

    protected function getRememberFormComponent(): Checkbox
    {
        return Checkbox::make('remember')
            ->label('Se souvenir de moi');
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label('Créer un compte artisan')
            ->url(filament()->getRegistrationUrl());
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Se connecter')
            ->submit('authenticate');
    }

    public function getTitle(): string
    {
        return 'Connexion sécurisée';
    }

    public function getHeading(): string
    {
        return 'Accédez à votre espace artisan';
    }
}
