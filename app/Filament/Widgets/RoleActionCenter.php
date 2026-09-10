<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AbsenceWorklist;
use App\Filament\Pages\CollectionCenter;
use App\Filament\Pages\GradeEntry;
use App\Filament\Pages\MissingGrades;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\ReportCardResource;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReportCard;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RoleActionCenter extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() !== true;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user?->role === 'teacher') {
            $classes = $user->teacherAssignments()->distinct('class_room_id')->count('class_room_id');
            $todaySessions = AttendanceSession::query()
                ->where('teacher_id', $user->id)
                ->whereDate('session_date', today())
                ->count();
            $unmarked = AttendanceSession::query()
                ->where('teacher_id', $user->id)
                ->whereDate('session_date', today())
                ->where('status', '!=', 'VALIDATED')
                ->count();
            $missingGrades = Assessment::query()
                ->where('teacher_id', $user->id)
                ->whereDoesntHave('grades')
                ->count();

            return [
                Stat::make('Classes du jour', $classes)
                    ->description('Ouvrir la prise de présence')
                    ->icon('heroicon-o-user-group')
                    ->url(url('/teacher/attendance')),
                Stat::make('Présences à terminer', $unmarked)
                    ->description($todaySessions.' appel(s) aujourd’hui')
                    ->color($unmarked ? 'warning' : 'success')
                    ->url(url('/teacher/attendance')),
                Stat::make('Évaluations sans notes', $missingGrades)
                    ->description('Compléter les évaluations')
                    ->color($missingGrades ? 'warning' : 'success')
                    ->url(MissingGrades::getUrl()),
            ];
        }

        if ($user?->role === 'accountant') {
            $outstanding = (int) Invoice::query()
                ->whereIn('status', [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE])
                ->get()
                ->sum(fn (Invoice $invoice): int => $invoice->balance());
            $overdue = Invoice::query()->where('status', Invoice::OVERDUE)->count();
            $recent = Payment::query()->where('status', Payment::CONFIRMED)->whereDate('paid_at', today())->sum('amount');

            return [
                Stat::make('Encaissé aujourd’hui', number_format((int) $recent, 0, ',', ' ').' FCFA')
                    ->icon('heroicon-o-banknotes')
                    ->url(PaymentResource::getUrl()),
                Stat::make('Reste à recouvrer', number_format($outstanding, 0, ',', ' ').' FCFA')
                    ->color($outstanding ? 'warning' : 'success')
                    ->url(CollectionCenter::getUrl()),
                Stat::make('Impayés vieillissants', $overdue)
                    ->description('Factures en retard')
                    ->color($overdue ? 'danger' : 'success')
                    ->url(CollectionCenter::getUrl(['status' => Invoice::OVERDUE])),
            ];
        }

        $absences = AttendanceRecord::query()->where('status', 'ABSENT')->whereHas('session', fn ($query) => $query->whereDate('session_date', today()))->count();
        $missingGrades = Grade::query()->whereNull('score')->count();
        $toPublish = ReportCard::query()->whereIn('status', ['review', 'conseil_de_classe', 'approved'])->count();
        $outstanding = (int) Invoice::query()->whereIn('status', [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE])->get()->sum(fn (Invoice $invoice): int => $invoice->balance());

        return [
            Stat::make('Absences à traiter', $absences)
                ->color($absences ? 'danger' : 'success')
                ->url(AbsenceWorklist::getUrl()),
            Stat::make('Notes manquantes', $missingGrades)
                ->color($missingGrades ? 'warning' : 'success')
                ->url(GradeEntry::getUrl()),
            Stat::make('Bulletins à publier', $toPublish)
                ->color($toPublish ? 'warning' : 'success')
                ->url(ReportCardResource::getUrl()),
            Stat::make('Impayés', number_format($outstanding, 0, ',', ' ').' FCFA')
                ->color($outstanding ? 'warning' : 'success')
                ->url(PaymentResource::getUrl()),
        ];
    }
}
