<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->isFinanceOperator() ?? false;
    }

    protected function getStats(): array
    {
        $expected = (int) Invoice::query()->whereNotIn('status', [Invoice::CANCELLED])->sum('total_amount');
        $collected = (int) Payment::query()->where('status', Payment::CONFIRMED)->sum('amount');
        $outstanding = max(0, $expected - $collected);
        return [
            Stat::make('Scolarité attendue', number_format($expected, 0, ',', ' ').' FCFA')->color('primary'),
            Stat::make('Encaissé', number_format($collected, 0, ',', ' ').' FCFA')->color('success'),
            Stat::make('Impayés', number_format($outstanding, 0, ',', ' ').' FCFA')->color('warning'),
            Stat::make('Taux de recouvrement', ($expected > 0 ? round($collected / $expected * 100, 1) : 0).'%')->color('primary'),
        ];
    }
}
