<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Models\School;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Écoles';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->alphaDash()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('email')->email(), TextInput::make('phone'), TextInput::make('address'),
            TextInput::make('city'), TextInput::make('timezone')->default('Africa/Lome'),
            TextInput::make('country')->default('Togo')->required(), Toggle::make('active')->default(true),
            TextInput::make('due_reminder_days')->label('Rappel d’échéance (jours avant)')
                ->numeric()->minValue(0)->maxValue(30)->default(3)->required(),
            Toggle::make('due_reminders_enabled')->label('Rappels WhatsApp d’échéance')->default(true),
            Toggle::make('single_operator_mode')->label('Mode école mono-utilisateur')
                ->helperText('Regroupe les accès quotidiens du directeur sans modifier les autorisations.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(), TextColumn::make('code')->searchable(),
            TextColumn::make('city'), IconColumn::make('active')->boolean(),
        ])->actions([EditAction::make()])->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSchools::route('/'), 'create' => Pages\CreateSchool::route('/create'), 'edit' => Pages\EditSchool::route('/{record}/edit')];
    }
}
