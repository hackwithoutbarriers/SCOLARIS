<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AcademicOverview;
use App\Filament\Widgets\AttendanceOverview;
use App\Filament\Widgets\FinanceOverview;
use App\Filament\Widgets\RoleActionCenter;
use App\Filament\Widgets\SchoolStatsOverview;
use App\Models\ClassRoom;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function mount(): void
    {
        // Le tableau de bord reste le point d’entrée commun ; les cartes orientent vers l’action.
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('date')->label('Date')->default(now()->toDateString()),
            Select::make('class_room_id')
                ->label('Classe')
                ->options(fn () => ClassRoom::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->native(false),
        ])->columns(2);
    }

    public function getWidgets(): array
    {
        $widgets = [RoleActionCenter::class, SchoolStatsOverview::class, AcademicOverview::class];

        if (auth()->user()?->isAdmin()) {
            $widgets[] = AttendanceOverview::class;
        }

        if (auth()->user()?->isFinanceOperator()) {
            $widgets[] = FinanceOverview::class;
        }

        return $widgets;
    }
}
