<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradeResource\Pages;
use App\Models\Grade;
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
        return auth()->user()?->isDirector() === true || auth()->user()?->role === 'teacher';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user?->role === 'teacher') {
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
            Forms\Components\Select::make('assessment_id')->label('Évaluation')->relationship('assessment', 'title')->required()->searchable()->preload(),
            Forms\Components\Select::make('student_id')->label('Élève')->relationship('student', 'student_number')->required()->searchable()->preload(),
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
            Tables\Columns\TextColumn::make('normalized_score')->label('Pourcentage')->toggleable()->extraAttributes(['class' => 'grade-cell']),
            Tables\Columns\TextColumn::make('grade_letter')->label('Mention')->badge()->toggleable(),
            Tables\Columns\TextColumn::make('validated_at')->label('Validée le')->dateTime()->toggleable(),
        ])->actions([
            Tables\Actions\EditAction::make()
                ->label('Modifier la note')
                ->visible(fn (Grade $record): bool => ! $record->validated_at && $record->assessment?->term?->status !== 'closed'),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListGrades::route('/'), 'create' => Pages\CreateGrade::route('/create'), 'edit' => Pages\EditGrade::route('/{record}/edit')];
    }
}
