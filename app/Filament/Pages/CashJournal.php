<?php

namespace App\Filament\Pages;

use App\Services\CashJournalService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Filament\Pages\Dashboard;
use App\Services\CashClosureService;
use App\Models\CashClosure;
use Filament\Notifications\Notification;

class CashJournal extends Page
{
    protected static ?string $navigationGroup = 'Finances';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Journal de caisse';
    protected static ?string $title = 'Journal de caisse';
    protected static string $view = 'filament.pages.cash-journal';
    public string $date;

    public static function canAccess(): bool { return auth()->user()?->isFinanceOperator() === true; }
    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }
    public function mount(): void { $this->date = today()->toDateString(); }
    public function getSummary(): array { return app(CashJournalService::class)->summary((int) auth()->user()->school_id, \Carbon\Carbon::parse($this->date)); }
    public function getClosure(): ?CashClosure { return CashClosure::query()->where('school_id', auth()->user()->school_id)->whereDate('closure_date', $this->date)->first(); }
    protected function getHeaderActions(): array {
        return [
            Action::make('close')
                ->label('Soumettre la clôture')
                ->visible(fn (): bool => auth()->user()?->hasRole('accountant') === true)
                ->form([
                    \Filament\Forms\Components\TextInput::make('physical_balance')->label('Solde physique compté (F CFA)')->numeric()->minValue(0)->required(),
                    \Filament\Forms\Components\Textarea::make('notes')->label('Justification de l’écart')->nullable(),
                ])
                ->action(function (array $data): void {
                    app(CashClosureService::class)->submit(
                        (int) auth()->user()->school_id,
                        $this->date,
                        (int) $data['physical_balance'],
                        $data['notes'] ?? null,
                    );
                    Notification::make()
                        ->title('Clôture soumise')
                        ->body('La clôture est en attente de validation par la direction.')
                        ->success()
                        ->send();
                }),
            Action::make('approve')
                ->label('Valider la clôture')
                ->visible(fn (): bool => auth()->user()?->isDirector() === true && $this->getClosure()?->status === 'pending')
                ->requiresConfirmation()
                ->action(function (): void {
                    app(CashClosureService::class)->approve($this->getClosure());
                    Notification::make()
                        ->title('Clôture validée')
                        ->success()
                        ->send();
                }),
            Action::make('dashboard')->label('Retour au tableau de bord')->icon('heroicon-o-arrow-left')->url(Dashboard::getUrl()),
        ];
    }
}
