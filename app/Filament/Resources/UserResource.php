<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Administration';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('first_name')->required(), TextInput::make('last_name')->required(),
            TextInput::make('name')->required(), TextInput::make('email')->email()->required(),
            TextInput::make('phone'), Select::make('school_id')->relationship('school', 'name')->searchable()->preload(),
            Select::make('role')->options(['super_admin' => 'Super Admin', 'director' => 'Directeur', 'teacher' => 'Enseignant', 'accountant' => 'Comptable'])->required(),
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
