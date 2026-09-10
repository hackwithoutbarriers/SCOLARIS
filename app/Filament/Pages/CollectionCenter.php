<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class CollectionCenter extends Page
{
    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Centre de recouvrement';

    protected static ?string $title = 'Centre de recouvrement';

    protected static string $view = 'filament.pages.collection-center';

    public string $status = 'all';

    public static function canAccess(): bool
    {
        return auth()->user()?->isFinanceOperator() === true;
    }

    public function mount(): void
    {
        $requestedStatus = request()->query('status');

        if (in_array($requestedStatus, [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE], true)) {
            $this->status = $requestedStatus;
        }
    }

    public function getInvoices(): Collection
    {
        return Invoice::query()
            ->with('student')
            ->whereIn('status', [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Invoice::OVERDUE])
            ->orderBy('due_date')
            ->limit(100)
            ->get();
    }

    public function getTotalOutstanding(): int
    {
        return $this->getInvoices()->sum(fn (Invoice $invoice): int => $invoice->balance());
    }
}
