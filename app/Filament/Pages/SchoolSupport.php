<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\School;
use App\Services\StaffInvitationService;
use App\Services\SuperAdminInterventionService;
use App\Support\Tenancy\SchoolContext;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

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

        if (! $this->hasActiveSelectedContext()) {
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
        abort_unless($this->hasActiveSelectedContext(), 403, 'Ouvrez d’abord le contexte temporaire de cette école.');
        app(SuperAdminInterventionService::class)->record($school, $action, ['source' => 'school_support']);
        Notification::make()->success()->title('Intervention journalisée.')->send();
        $this->redirect($url);
    }

    public function getInterventions(): Collection
    {
        return AuditLog::query()->where('school_id', $this->school)->where('action', 'like', 'super_admin.%')->latest()->limit(20)->get();
    }

    public function openContext(): void
    {
        $school = $this->getSelectedSchool();
        abort_unless($school, 422, 'Sélectionnez une école.');

        $currentSchoolId = (int) session('scolaris_intervention.school_id', 0);
        if ($currentSchoolId && $currentSchoolId !== (int) $school->id) {
            $currentSchool = School::withoutGlobalScopes()->find($currentSchoolId);
            if ($currentSchool && auth()->user()->hasActiveSchoolIntervention($currentSchoolId)) {
                app(SuperAdminInterventionService::class)->record($currentSchool, 'context_closed');
            }
            session()->forget('scolaris_intervention');
            app(SchoolContext::class)->clear();
        }

        $expiresAt = now()->addHour();
        app(SuperAdminInterventionService::class)->record($school, 'context_opened', ['expires_at' => $expiresAt->toIso8601String()]);
        session([
            'scolaris_intervention.school_id' => $school->id,
            'scolaris_intervention.expires_at' => $expiresAt->timestamp,
        ]);
        app(SchoolContext::class)->set((int) $school->id);
        $this->redirect(Dashboard::getUrl());
    }

    public function leaveContext(): void
    {
        $currentSchoolId = (int) session('scolaris_intervention.school_id', 0);
        $currentSchool = $currentSchoolId ? School::withoutGlobalScopes()->find($currentSchoolId) : null;
        if ($currentSchool && auth()->user()->hasActiveSchoolIntervention($currentSchoolId)) {
            app(SuperAdminInterventionService::class)->record($currentSchool, 'context_closed');
        }
        session()->forget('scolaris_intervention');
        app(SchoolContext::class)->clear();
        $this->redirect(static::getUrl());
    }

    public function hasActiveSelectedContext(): bool
    {
        return $this->school !== null
            && auth()->user()?->hasActiveSchoolIntervention($this->school) === true;
    }

    public function createSchoolAndInvite(array $data): void
    {
        $school = School::create([
            'name' => $data['school_name'], 'code' => $data['school_code'],
            'country' => 'Togo', 'timezone' => 'Africa/Lome', 'active' => true,
        ]);
        app(StaffInvitationService::class)->create(auth()->user(), [
            'school_id' => $school->id, 'name' => $data['director_name'],
            'email' => $data['director_email'] ?? null, 'phone' => $data['director_phone'] ?? null,
            'role' => 'director',
        ], platformOnboarding: true);
        $this->school = $school->id;
        Notification::make()->success()->title('École créée et invitation envoyée.')->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSchool')
                ->label('Créer une école et inviter son directeur')
                ->form([
                    Forms\Components\TextInput::make('school_name')->label('Nom de l’école')->required(),
                    Forms\Components\TextInput::make('school_code')->label('Code de l’école')->required()->alphaDash(),
                    Forms\Components\TextInput::make('director_name')->label('Nom du directeur')->required(),
                    Forms\Components\TextInput::make('director_email')->label('E-mail du directeur')->email()->nullable(),
                    Forms\Components\TextInput::make('director_phone')->label('Téléphone / WhatsApp')->nullable(),
                ])
                ->action(fn (array $data) => $this->createSchoolAndInvite($data)),
            Action::make('dashboard')->label('Retour au tableau de bord')->icon('heroicon-o-arrow-left')->url(Dashboard::getUrl()),
        ];
    }
}
