<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassRoomResource\Pages;
use App\Models\ClassRoom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClassRoomResource extends Resource
{
    protected static ?string $model = ClassRoom::class;

    protected static ?string $navigationGroup = 'Paramétrage scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required(),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('grade_level'),
            Forms\Components\TextInput::make('cycle')->label('Cycle')->helperText('Primaire, secondaire 1, secondaire 2, ou toute valeur propre à l’école.'),
            Forms\Components\TextInput::make('filiere')->label('Filière')->helperText('Général, technique/professionnel, ou toute valeur propre à l’école.'),
            Forms\Components\TextInput::make('capacity')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(), Tables\Columns\TextColumn::make('academicYear.name')->label('Année'),             Tables\Columns\TextColumn::make('grade_level')->label('Niveau'), Tables\Columns\TextColumn::make('cycle')->label('Cycle'), Tables\Columns\TextColumn::make('filiere')->label('Filière'), Tables\Columns\TextColumn::make('enrollments_count')->counts('enrollments')->label('Élèves'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListClassRooms::route('/'), 'create' => Pages\CreateClassRoom::route('/create'), 'edit' => Pages\EditClassRoom::route('/{record}/edit')];
    }
}
