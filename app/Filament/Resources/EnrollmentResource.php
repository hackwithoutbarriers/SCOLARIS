<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Models\Enrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationGroup = 'Paramétrage scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')->relationship('student', 'first_name')->searchable()->required(),
            Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required(),
            Forms\Components\Select::make('class_room_id')->relationship('classRoom', 'name')->required(),
            Forms\Components\DatePicker::make('enrolled_at')->default(now())->required(),
            Forms\Components\Select::make('status')->label('Statut')->options(['active' => 'Active', 'withdrawn' => 'Retiré'])->default('active')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('student.full_name')->label('Élève'), Tables\Columns\TextColumn::make('classRoom.name')->label('Classe'), Tables\Columns\TextColumn::make('academicYear.name')->label('Année'), Tables\Columns\TextColumn::make('status')->label('Statut')->badge()])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEnrollments::route('/'), 'create' => Pages\CreateEnrollment::route('/create'), 'edit' => Pages\EditEnrollment::route('/{record}/edit')];
    }
}
