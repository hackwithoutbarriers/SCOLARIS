<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AcademicYearResource;
use App\Filament\Resources\ClassRoomResource;
use App\Filament\Resources\RegistrationRequestResource;
use App\Models\Enrollment;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\StudentImportBatch;
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

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getSteps(): array
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $activeImport = $schoolId === null ? null : StudentImportBatch::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', ['uploaded', 'analyzed', 'mapped', 'previewed', 'processing', 'partial', 'failed'])
            ->latest('created_at')
            ->latest('id')
            ->first();
        $studentStepDescription = $activeImport
            ? 'Un import est à reprendre ('.$this->statusLabel($activeImport->status).'). Continuez la vérification sans retéléverser le fichier.'
            : 'Importez votre première cohorte et vérifiez les inscriptions.';

        return [
            ['title' => 'École', 'description' => 'Votre établissement est rattaché à votre compte.', 'complete' => $schoolId !== null, 'url' => Dashboard::getUrl()],
            ['title' => 'Année scolaire et périodes', 'description' => 'Créez l’année et ses périodes avant les classes.', 'complete' => AcademicYear::query()->where('school_id', $schoolId)->exists(), 'url' => AcademicYearResource::getUrl()],
            ['title' => 'Classes', 'description' => 'Organisez les classes et leurs niveaux.', 'complete' => ClassRoom::query()->where('school_id', $schoolId)->exists(), 'url' => ClassRoomResource::getUrl()],
            ['title' => 'Utilisateurs', 'description' => 'Invitez les enseignants et comptables nécessaires.', 'complete' => User::query()->where('school_id', $schoolId)->whereKey('id', '!=', $user->id)->exists(), 'url' => RegistrationRequestResource::getUrl()],
            ['title' => 'Élèves', 'description' => $studentStepDescription, 'complete' => Enrollment::query()->where('school_id', $schoolId)->exists(), 'url' => $activeImport ? StudentImport::getUrl(['batch' => $activeImport->id]) : StudentImport::getUrl()],
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'uploaded' => 'fichier reçu',
            'analyzed' => 'analyse terminée',
            'mapped' => 'colonnes à vérifier',
            'previewed' => 'simulation prête',
            'processing' => 'traitement en cours',
            'partial' => 'revue à terminer',
            'failed' => 'traitement interrompu',
            default => 'import en cours',
        };
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
