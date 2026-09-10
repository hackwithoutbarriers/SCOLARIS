<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AcademicYearResource;
use App\Filament\Resources\ClassRoomResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\UserResource;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SchoolOnboarding extends Page
{
    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Démarrage de l’école';

    protected static ?string $title = 'Démarrage de l’école';

    protected static string $view = 'filament.pages.school-onboarding';

    public static function canAccess(): bool
    {
        return auth()->user()?->isDirector() === true;
    }

    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }

    public function getSteps(): array
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        return [
            ['title' => 'École', 'description' => 'Votre établissement est rattaché à votre compte.', 'complete' => $schoolId !== null, 'url' => Dashboard::getUrl()],
            ['title' => 'Année scolaire et périodes', 'description' => 'Créez l’année et ses périodes avant les classes.', 'complete' => AcademicYear::query()->exists(), 'url' => AcademicYearResource::getUrl()],
            ['title' => 'Classes', 'description' => 'Organisez les classes et leurs niveaux.', 'complete' => ClassRoom::query()->exists(), 'url' => ClassRoomResource::getUrl()],
            ['title' => 'Utilisateurs', 'description' => 'Invitez les enseignants et comptables nécessaires.', 'complete' => User::query()->where('school_id', $schoolId)->whereKey('id', '!=', $user->id)->exists(), 'url' => UserResource::getUrl()],
            ['title' => 'Élèves', 'description' => 'Importez ou créez les élèves de l’établissement.', 'complete' => Student::query()->exists(), 'url' => StudentResource::getUrl()],
        ];
    }

    public function getProgressProperty(): int
    {
        $steps = $this->getSteps();

        return (int) round(collect($steps)->where('complete', true)->count() / count($steps) * 100);
    }

    public function next(): void
    {
        $steps = $this->getSteps();
        $current = max(1, min((int) auth()->user()->onboarding_step, count($steps)));

        if (! $steps[$current - 1]['complete']) {
            Notification::make()->warning()->title('Étape à terminer')->body('Terminez cette étape avant de poursuivre.')->send();

            return;
        }

        $user = auth()->user();
        $user->onboarding_step = min($current + 1, count($steps));
        if ($user->onboarding_step === count($steps) && $this->progress === 100) {
            $user->onboarding_completed_at = now();
        }
        $user->save();
    }

    public function back(): void
    {
        $user = auth()->user();
        $user->onboarding_step = max(1, (int) $user->onboarding_step - 1);
        $user->save();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('continue')
                ->label('Reprendre plus tard')
                ->color('gray')
                ->url(Dashboard::getUrl()),
        ];
    }
}
