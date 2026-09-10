<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Écoles actives', School::query()->where('active', true)->count())
                ->description('Établissements opérationnels')
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),
            Stat::make('Utilisateurs actifs', User::query()->where('is_active', true)->where('role', '!=', 'super_admin')->count())
                ->description('Équipes des écoles')
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make('Élèves suivis', Student::query()->count())
                ->description('Toutes les écoles')
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),
            Stat::make('Factures en retard', Invoice::query()->where('status', Invoice::OVERDUE)->count())
                ->description('Points nécessitant une assistance')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning'),
            Stat::make('Abonnements actifs', SchoolSubscription::query()->whereIn('status', ['trial', 'active'])->count())
                ->description('Écoles couvertes par un plan')
                ->icon('heroicon-o-credit-card')
                ->color('success'),
        ];
    }
}
