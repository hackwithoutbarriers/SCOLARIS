<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherAssignmentResource\Pages;
use App\Models\TeacherAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherAssignmentResource extends Resource
{
    protected static ?string $model = TeacherAssignment::class;

    protected static ?string $navigationGroup = 'Paramétrage scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('teacher_id')->relationship('teacher', 'name')->searchable()->required(),
            Forms\Components\Select::make('class_room_id')->relationship('classRoom', 'name')->required(),
            Forms\Components\Select::make('subject_id')->relationship('subject', 'name')->required(),
            Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('teacher.name')->label('Enseignant'), Tables\Columns\TextColumn::make('classRoom.name')->label('Classe'), Tables\Columns\TextColumn::make('subject.name')->label('Matière'), Tables\Columns\TextColumn::make('academicYear.name')->label('Année'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListTeacherAssignments::route('/'), 'create' => Pages\CreateTeacherAssignment::route('/create'), 'edit' => Pages\EditTeacherAssignment::route('/{record}/edit')];
    }
}
