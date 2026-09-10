<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\School;
use App\Services\SuperAdminInterventionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Filament\Pages\Dashboard;
use Illuminate\Database\Eloquent\Collection;

class SchoolSupport extends Page
{
    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Assistance écoles';

    protected static ?string $title = 'Assistance école';

    protected static string $view = 'filament.pages.school-support';

    public ?int $school = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }

    public function mount(): void
    {
        $requestedSchool = request()->query('school');
        $this->school = $requestedSchool && School::query()->whereKey($requestedSchool)->exists()
            ? (int) $requestedSchool
            : School::query()->orderBy('name')->value('id');
    }

    public function getSchools(): Collection
    {
        return School::query()->orderBy('name')->get();
    }

    public function getSelectedSchool(): ?School
    {
        return $this->school ? School::query()->find($this->school) : null;
    }

    public function getMetrics(): array
    {
        $selectedSchool = $this->getSelectedSchool();

        if (! $selectedSchool) {
            return ['users' => 0, 'students' => 0, 'classes' => 0, 'overdue' => 0];
        }

        return [
            'users' => $selectedSchool->users()->where('role', '!=', 'super_admin')->count(),
            'students' => $selectedSchool->students()->count(),
            'classes' => $selectedSchool->classRooms()->count(),
            'overdue' => $selectedSchool->invoices()->where('status', Invoice::OVERDUE)->count(),
        ];
    }

    public function recordIntervention(string $action, string $url): void
    {
        $school = $this->getSelectedSchool();
        abort_unless($school, 422, 'Sélectionnez une école avant toute intervention.');
        app(SuperAdminInterventionService::class)->record($school, $action, ['source' => 'school_support']);
        Notification::make()->success()->title('Intervention journalisée.')->send();
        $this->redirect($url);
    }

    public function getInterventions(): Collection
    {
        return AuditLog::query()->where('school_id', $this->school)->where('action', 'like', 'super_admin.%')->latest()->limit(20)->get();
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('dashboard')->label('Retour au tableau de bord')->icon('heroicon-o-arrow-left')->url(Dashboard::getUrl())];
    }
}
