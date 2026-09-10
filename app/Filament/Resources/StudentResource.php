<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Personnes';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isDirector() === true || auth()->user()?->isSecretary() === true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Élève')->schema([
                Forms\Components\TextInput::make('admission_number')->required(),
                Forms\Components\TextInput::make('first_name')->required(),
                Forms\Components\TextInput::make('last_name')->required(),
                Forms\Components\DatePicker::make('date_of_birth'),
                Forms\Components\Select::make('gender')->label('Genre')->options(['female' => 'Féminin', 'male' => 'Masculin', 'other' => 'Autre']),
                Forms\Components\Select::make('status')->label('Statut')->options(['active' => 'Actif', 'inactive' => 'Inactif'])->default('active')->required(),
                Forms\Components\TextInput::make('email')->email(),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\Textarea::make('address')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('admission_number')->searchable(),
            Tables\Columns\TextColumn::make('full_name')->label('Nom complet')->searchable(['first_name', 'last_name']),
            Tables\Columns\TextColumn::make('email')->toggleable(),
            Tables\Columns\TextColumn::make('phone')->toggleable(),
            Tables\Columns\BadgeColumn::make('status')->colors(['success' => 'active', 'danger' => 'inactive']),
        ])->headerActions([
            Tables\Actions\Action::make('exportCsv')->label('Exporter CSV')->url(fn (): string => route('students.registry.csv'))->openUrlInNewTab(),
            Tables\Actions\Action::make('exportPdf')->label('Exporter PDF')->url(fn (): string => route('students.registry.pdf'))->openUrlInNewTab(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStudents::route('/'), 'create' => Pages\CreateStudent::route('/create'), 'edit' => Pages\EditStudent::route('/{record}/edit')];
    }
}
