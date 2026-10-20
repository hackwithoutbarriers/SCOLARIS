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

    protected static ?string $navigationGroup = 'Élèves et familles';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Inscriptions';

    protected static ?string $modelLabel = 'inscription';

    protected static ?string $pluralModelLabel = 'inscriptions';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isDirector() === true
            || auth()->user()?->isSecretary() === true
            || (auth()->user()?->isSuperAdmin() === true && auth()->user()->hasActiveSchoolIntervention());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')
                ->label('Élève')
                ->relationship('student', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record): string => trim($record->first_name.' '.$record->last_name).' — '.$record->student_number)
                ->searchable(['first_name', 'last_name', 'student_number'])
                ->required(),
            Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required(),
            Forms\Components\Select::make('class_room_id')->relationship('classRoom', 'name')->required(),
            Forms\Components\DatePicker::make('enrolled_at')->default(now())->required(),
            Forms\Components\Select::make('status')->label('Statut')->options(['active' => 'Active', 'withdrawn' => 'Retiré'])->default('active')->required(),
            Forms\Components\CheckboxList::make('optional_fee_types')->label('Services optionnels')->options(['canteen' => 'Cantine', 'transport' => 'Transport'])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('student.full_name')->label('Élève')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('classRoom.name')->label('Classe')->searchable(),
            Tables\Columns\TextColumn::make('academicYear.name')->label('Année scolaire'),
            Tables\Columns\TextColumn::make('enrollment_date')->label('Inscrit le')->date('d/m/Y'),
            Tables\Columns\TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Active' : 'Retirée'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Modifier le suivi'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEnrollments::route('/'), 'edit' => Pages\EditEnrollment::route('/{record}/edit')];
    }
}
