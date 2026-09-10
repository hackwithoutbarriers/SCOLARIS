<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Filament\Pages\Dashboard;
use App\Http\Controllers\NotificationController;
use App\Services\PhoneNumberFormatter;
use App\Services\Notifications\SchoolMailerService;
use Illuminate\Database\Eloquent\Collection;

class CollectionCenter extends Page
{
    protected static ?string $navigationGroup = 'Finances';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Centre de recouvrement';

    protected static ?string $title = 'Centre de recouvrement';

    protected static string $view = 'filament.pages.collection-center';

    public string $status = 'all';

    public static function canAccess(): bool
    {
        return auth()->user()?->isFinanceOperator() === true;
    }

    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }

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
            ->with(['student.guardians', 'student.school'])
            ->whereIn('status', [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Invoice::OVERDUE])
            ->orderBy('due_date')
            ->limit(100)
            ->get();
    }

    public function whatsappUrl(Invoice $invoice): ?string
    {
        $guardian = $invoice->student?->primaryGuardian()->first() ?: $invoice->student?->guardians()->first();
        if (! $guardian || ! PhoneNumberFormatter::toE164((string) $guardian->phone)) {
            return null;
        }

        return NotificationController::whatsappUrl($guardian, 'payment_reminder', [
            'guardian_name' => $guardian->full_name, 'student_name' => $invoice->student->full_name,
            'amount' => number_format($invoice->balance(), 0, ',', ' ').' '.$invoice->currency,
            'school_name' => $invoice->student->school?->name ?: '', 'student_id' => $invoice->student_id,
        ], ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]);
    }

    public function emailUrl(Invoice $invoice): ?string
    {
        $guardian = $invoice->student?->primaryGuardian()->first() ?: $invoice->student?->guardians()->first();
        if (! $guardian?->email || ! app(SchoolMailerService::class)->isConfigured($guardian->school)) {
            return null;
        }

        return NotificationController::emailUrl($guardian, 'payment_reminder', [
            'guardian_name' => $guardian->full_name, 'student_name' => $invoice->student->full_name,
            'amount' => number_format($invoice->balance(), 0, ',', ' ').' '.$invoice->currency,
            'school_name' => $invoice->student->school?->name ?: '', 'student_id' => $invoice->student_id,
        ], ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]);
    }

    public function getTotalOutstanding(): int
    {
        return $this->getInvoices()->sum(fn (Invoice $invoice): int => $invoice->balance());
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('dashboard')->label('Retour au tableau de bord')->icon('heroicon-o-arrow-left')->url(Dashboard::getUrl())];
    }
}
