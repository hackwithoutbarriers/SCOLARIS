<?php

namespace App\Filament\Pages;

use App\Services\CashJournalService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Filament\Pages\Dashboard;

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
    protected function getHeaderActions(): array { return [Action::make('dashboard')->label('Retour au tableau de bord')->icon('heroicon-o-arrow-left')->url(Dashboard::getUrl())]; }
}
