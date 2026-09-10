<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentResource\Pages;
use App\Models\Assessment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AssessmentResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static ?string $navigationGroup = 'Scolarité';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Évaluations';
    protected static ?string $modelLabel = 'Évaluation';
    protected static ?string $pluralModelLabel = 'Évaluations';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isDirector() === true || auth()->user()?->role === 'teacher';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user?->role === 'teacher') {
            $query->where('teacher_id', $user->id)
                ->whereHas('subjectConfig.classRoom.teacherAssignments', fn (Builder $assignment) => $assignment->where('teacher_id', $user->id))
                ->whereHas('subjectConfig', fn (Builder $config) => $config->whereHas('classRoom.teacherAssignments', fn (Builder $assignment) => $assignment->where('teacher_id', $user->id)));
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('subject_config_id')->label('Matière et classe')->relationship('subjectConfig', 'id')->getOptionLabelFromRecordUsing(fn (\App\Models\SubjectConfig $record): string => ($record->subject?->name ?? 'Matière').' — '.($record->classRoom?->name ?? 'Classe'))->required()->searchable()->preload(),
            Forms\Components\Select::make('term_id')->relationship('term', 'name')->required()->preload(),
            Forms\Components\Select::make('teacher_id')->label('Enseignant')->relationship('teacher', 'name')->searchable()->preload(),
            Forms\Components\TextInput::make('title')->label('Titre')->required()->maxLength(255),
            Forms\Components\DatePicker::make('assessment_date')->label('Date de l’évaluation'),
            Forms\Components\TextInput::make('max_score')->label('Barème maximal')->numeric()->minValue(0.01)->required(),
            Forms\Components\TextInput::make('weight')->label('Coefficient')->numeric()->minValue(0)->required(),
            Forms\Components\Select::make('status')->label('Statut')->options(['draft' => 'Brouillon', 'published' => 'Publié'])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('title')->label('Titre')->searchable(), Tables\Columns\TextColumn::make('subjectConfig.subject.name')->label('Matière'), Tables\Columns\TextColumn::make('term.name')->label('Période'), Tables\Columns\TextColumn::make('status')->label('Statut')->badge(), Tables\Columns\TextColumn::make('assessment_date')->label('Date')->date()])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAssessments::route('/'), 'create' => Pages\CreateAssessment::route('/create'), 'edit' => Pages\EditAssessment::route('/{record}/edit')];
    }
}
