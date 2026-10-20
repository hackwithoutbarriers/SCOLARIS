<?php

namespace App\Filament\Pages;

use App\Models\Assessment;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MissingGrades extends Page
{
    protected static ?string $navigationGroup = 'Scolarité';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Notes à compléter';

    protected static ?string $title = 'Notes à compléter';

    protected static string $view = 'filament.pages.missing-grades';

    public static function canAccess(): bool
    {
        return auth()->user()?->isDirector() === true
            || auth()->user()?->isCenseur() === true
            || auth()->user()?->hasRole('teacher') === true
            || (auth()->user()?->isSuperAdmin() === true && auth()->user()->hasActiveSchoolIntervention());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getAssessments(): Collection
    {
        $user = auth()->user();

        return Assessment::query()
            ->with(['subjectConfig.subject', 'term', 'teacher'])
            ->whereDoesntHave('grades')
            ->when($user?->hasRole('teacher') === true, fn (Builder $query) => $query->where('teacher_id', $user->id))
            ->when($user?->hasRole('teacher') === true, fn (Builder $query) => $query->whereHas(
                'subjectConfig.classRoom.teacherAssignments',
                fn (Builder $assignment) => $assignment->where('teacher_id', $user->id)
            ))
            ->latest('assessment_date')
            ->limit(100)
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('dashboard')->label('Retour au tableau de bord')->icon('heroicon-o-arrow-left')->url(Dashboard::getUrl())];
    }
}
