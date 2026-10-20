<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\School;
use App\Models\User;
use App\Services\PhoneNumberFormatter;
use App\Support\Tenancy\SchoolContext;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ClosureValidationRule;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() === true
            && auth()->user()->hasActiveSchoolIntervention();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('first_name')->required(), TextInput::make('last_name')->required(),
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(fn (Get $get): bool => blank($get('phone'))),
            TextInput::make('phone')->tel()->rules([
                new ClosureValidationRule(function (string $attribute, mixed $value, Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $phone = PhoneNumberFormatter::toE164((string) $value);
                    if (! $phone) {
                        $fail('Saisissez un numéro togolais valide, par exemple +22890000000.');

                        return;
                    }

                    $query = User::withoutGlobalScopes()->where('login_phone', $phone);
                    $recordId = request()->route('record');
                    if (is_numeric($recordId)) {
                        $query->whereKeyNot((int) $recordId);
                    }
                    if ($query->exists()) {
                        $fail('Ce numéro est déjà associé à un compte.');
                    }
                }),
            ]),
            Select::make('school_id')
                ->options(function (): array {
                    $schoolId = app(SchoolContext::class)->id();

                    return $schoolId ? School::query()->whereKey($schoolId)->pluck('name', 'id')->all() : [];
                })
                ->default(fn (): ?int => app(SchoolContext::class)->id())
                ->disabled()
                ->required(),
            Select::make('role')->label('Rôle')->options([
                'director' => 'Directeur',
                'teacher' => 'Enseignant',
                'accountant' => 'Comptable',
                'secretary' => 'Secrétaire',
                'censeur' => 'Censeur',
            ])->required(),
            TextInput::make('password')->password()->dehydrated(fn ($state) => filled($state))->rules([Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()])->required(fn (string $operation): bool => $operation === 'create'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nom')->searchable(), TextColumn::make('email')->label('E-mail')->searchable(), TextColumn::make('school.name')->label('École'), TextColumn::make('role')->label('Rôle'),
            IconColumn::make('is_active')->boolean(),
        ])->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListUsers::route('/'), 'create' => Pages\CreateUser::route('/create'), 'edit' => Pages\EditUser::route('/{record}/edit')];
    }
}
