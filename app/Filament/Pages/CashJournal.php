<?php

namespace App\Filament\Pages;

use App\Services\CashJournalService;
use Filament\Pages\Page;

class CashJournal extends Page
{
    protected static ?string $navigationGroup = 'Finances';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Journal de caisse';
    protected static ?string $title = 'Journal de caisse';
    protected static string $view = 'filament.pages.cash-journal';
    public string $date;

    public static function canAccess(): bool { return auth()->user()?->isFinanceOperator() === true; }
    public function mount(): void { $this->date = today()->toDateString(); }
    public function getSummary(): array { return app(CashJournalService::class)->summary((int) auth()->user()->school_id, \Carbon\Carbon::parse($this->date)); }
}
