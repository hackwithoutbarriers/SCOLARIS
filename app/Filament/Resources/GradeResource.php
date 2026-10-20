<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradeResource\Pages;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static ?string $navigationGroup = 'Scolarité';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Notes';

    protected static ?string $modelLabel = 'Note';

    protected static ?string $pluralModelLabel = 'Notes';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isDirector() === true
            || $user?->isCenseur() === true
            || $user?->hasRole('teacher') === true
            || ($user?->isSuperAdmin() === true && $user->hasActiveSchoolIntervention());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user?->hasRole('teacher') === true) {
            $query->whereHas('assessment', fn (Builder $assessment) => $assessment
                ->where('teacher_id', $user->id)
                ->whereHas('subjectConfig', fn (Builder $config) => $config
                    ->whereHas('classRoom.teacherAssignments', fn (Builder $assignment) => $assignment
                        ->where('teacher_id', $user->id)
                        ->whereColumn('subject_id', 'subject_configs.subject_id'))))
                ->whereHas('student.enrollments.classRoom.teacherAssignments', fn (Builder $assignment) => $assignment
                    ->where('teacher_id', $user->id));
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('assessment_id')
                ->label('Évaluation')
                ->options(fn (): array => AssessmentResource::getEloquentQuery()
                    ->with('subjectConfig.subject')
                    ->get()
                    ->mapWithKeys(fn (Assessment $assessment): array => [
                        $assessment->id => ($assessment->subjectConfig?->subject?->name ?: 'Matière').' — '.$assessment->title,
                    ])->all())
                ->required()
                ->searchable(),
            Forms\Components\Select::make('student_id')
                ->label('Élève')
                ->options(fn (): array => Student::query()
                    ->when(auth()->user()?->hasRole('teacher') === true && auth()->user()?->isDirector() !== true, fn (Builder $query) => $query->whereHas(
                        'enrollments.classRoom.teacherAssignments',
                        fn (Builder $assignment) => $assignment->where('teacher_id', Auth::id())
                    ))
                    ->orderBy('last_name')
                    ->get()
                    ->mapWithKeys(fn (Student $student): array => [$student->id => $student->full_name])
                    ->all())
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('score')->label('Note')->numeric()->minValue(0)->required()->helperText('La note ne peut pas dépasser le barème maximal.'),
            Forms\Components\Textarea::make('remarks'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('assessment.title')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('student.full_name')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('score')->label('Note')->numeric()->suffix(fn (Grade $record): string => ' / '.$record->assessment?->max_score)
                ->icon(fn (Grade $record): ?string => $record->validated_at || $record->assessment?->term?->status === 'closed' ? 'heroicon-o-lock-closed' : null)
                ->color(fn (Grade $record): string => $record->validated_at || $record->assessment?->term?->status === 'closed' ? 'gray' : 'primary')
                ->extraAttributes(fn (Grade $record): array => ($record->validated_at || $record->assessment?->term?->status === 'closed') ? ['class' => 'grade-cell opacity-60 bg-gray-100'] : ['class' => 'grade-cell']),
            Tables\Columns\TextColumn::make('normalized_score')->label('Pourcentage')
                ->formatStateUsing(fn ($state): string => $state === null ? '—' : number_format((float) $state, 0, ',', ' ').'%')
                ->toggleable()->extraAttributes(['class' => 'grade-cell']),
            Tables\Columns\TextColumn::make('grade_letter')->label('Mention')->badge()->toggleable(),
            Tables\Columns\TextColumn::make('validated_at')->label('Validée le')->dateTime()->toggleable(),
        ])->actions([
            Tables\Actions\EditAction::make()
                ->label('Modifier la note')
                ->visible(fn (Grade $record): bool => auth()->user()?->isCenseur() !== true
                    && ! $record->validated_at
                    && $record->assessment?->term?->status !== 'closed'),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListGrades::route('/'), 'create' => Pages\CreateGrade::route('/create'), 'edit' => Pages\EditGrade::route('/{record}/edit')];
    }
}
