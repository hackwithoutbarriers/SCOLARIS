<?php

namespace App\Filament\Pages;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Services\GradeCalculationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Get;
use Filament\Forms\Set;

class GradeEntry extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Academic';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $title = 'Saisie rapide des notes';
    protected static string $view = 'filament.pages.grade-entry';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['grades' => []]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('assessment_id')
                ->label('Évaluation')
                ->options(fn () => Assessment::query()->with('subjectConfig.subject')->latest('assessment_date')->get()->mapWithKeys(fn (Assessment $assessment) => [
                    $assessment->id => $assessment->subjectConfig?->subject?->name.' — '.$assessment->title.' (max '.$assessment->max_score.')',
                ]))
                ->searchable()
                ->live()
                ->required()
                ->afterStateUpdated(function ($state, Set $set): void {
                    $assessment = $state ? Assessment::query()->with('subjectConfig')->find($state) : null;
                    $classRoomId = $assessment?->subjectConfig?->class_room_id;
                    $students = $assessment
                        ? Enrollment::query()->with('student')->where('academic_year_id', $assessment->subjectConfig?->academic_year_id)
                            ->when($classRoomId, fn ($query) => $query->where('class_room_id', $classRoomId))
                            ->where('status', 'active')->get()->unique('student_id')
                        : collect();
                    $set('grades', $students->map(fn (Enrollment $enrollment) => [
                        'student_id' => $enrollment->student_id,
                        'student_name' => $enrollment->student?->full_name,
                        'score' => $enrollment->student?->grades()->where('assessment_id', $assessment?->id)->value('score'),
                    ])->values()->all());
                }),
            Repeater::make('grades')
                ->label('Notes')
                ->schema([
                    TextInput::make('student_name')->label('Élève')->disabled()->dehydrated(false),
                    TextInput::make('score')->label('Note')->numeric()->minValue(0)->inputMode('decimal')->placeholder('—'),
                    TextInput::make('student_id')->hidden()->required(),
                ])
                ->columns(['default' => 1, 'md' => 3])
                ->addable(false)->deletable(false)->reorderable(false)
                ->defaultItems(0),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer les notes')
                ->color('primary')
                ->action('save'),
        ];
    }

    public function save(GradeCalculationService $calculator): void
    {
        $state = $this->form->getState();
        $assessment = Assessment::query()->findOrFail($state['assessment_id']);
        $saved = 0;
        foreach ($state['grades'] ?? [] as $row) {
            if ($row['score'] === null || $row['score'] === '') {
                continue;
            }
            $student = \App\Models\Student::query()->findOrFail($row['student_id']);
            $calculator->saveGrade($assessment, $student, $row['score']);
            $saved++;
        }
        Notification::make()->success()->title('Notes enregistrées')->body("{$saved} note(s) enregistrée(s).")->send();
    }
}
