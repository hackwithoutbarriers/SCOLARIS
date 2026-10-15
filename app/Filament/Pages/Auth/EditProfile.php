<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations personnelles')
                    ->description('Modifiez les informations utilisées pour votre compte administrateur.')
                    ->schema([
                        $this->getNameFormComponent()
                            ->label('Nom complet')
                            ->autocomplete('name'),
                        $this->getEmailFormComponent()
                            ->label('Adresse e-mail')
                            ->autocomplete('email'),
                    ]),
                Section::make('Sécurité')
                    ->description('Laissez les champs de mot de passe vides pour conserver votre mot de passe actuel.')
                    ->schema([
                        $this->getPasswordFormComponent()
                            ->label('Nouveau mot de passe')
                            ->autocomplete('new-password'),
                        $this->getPasswordConfirmationFormComponent()
                            ->label('Confirmation du nouveau mot de passe')
                            ->autocomplete('new-password'),
                    ]),
                Section::make('Authentification à deux facteurs')
                    ->description('Renforcez la sécurité du compte avec une application d’authentification.')
                    ->schema([
                        TextInput::make('two_factor_secret')
                            ->label('Clé 2FA')
                            ->readOnly()
                            ->helperText('Ajoutez cette clé dans Google Authenticator ou Microsoft Authenticator.')
                            ->dehydrated(true)
                            ->default(fn (): string => $this->getUser()->two_factor_secret ?? $this->generateTwoFactorSecret()),
                        TextInput::make('two_factor_code')
                            ->label('Code de vérification')
                            ->numeric()
                            ->length(6)
                            ->prefixIcon('heroicon-o-shield-check')
                            ->autocomplete('one-time-code')
                            ->helperText('Saisissez le code affiché par votre application pour confirmer l’activation du 2FA.')
                            ->dehydrated(false)
                            ->visible(fn (): bool => filled($this->getUser()->two_factor_secret) && blank($this->getUser()->two_factor_confirmed_at)),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->getUser();
        $secret = $data['two_factor_secret'] ?? $user->two_factor_secret ?? null;

        if (filled($secret) && filled($data['two_factor_code'] ?? null)) {
            $code = trim((string) $data['two_factor_code']);
            $google2fa = app(Google2FA::class);

            if (! $google2fa->verifyKey($secret, $code, 8)) {
                throw ValidationException::withMessages([
                    'data.two_factor_code' => 'Le code de vérification 2FA est invalide.',
                ]);
            }

            $data['two_factor_secret'] = $secret;
            $data['two_factor_confirmed_at'] = now();
        }

        return $data;
    }

    protected function generateTwoFactorSecret(): string
    {
        $user = $this->getUser();

        if (blank($user->two_factor_secret)) {
            $user->forceFill([
                'two_factor_secret' => app(Google2FA::class)->generateSecretKey(),
            ]);

            $user->saveQuietly();
        }

        return $user->fresh()->two_factor_secret;
    }
}
