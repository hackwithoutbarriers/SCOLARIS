<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AcademicOverview;
use App\Filament\Widgets\AttendanceOverview;
use App\Filament\Widgets\FinanceOverview;
use App\Filament\Widgets\RoleActionCenter;
use App\Filament\Widgets\SchoolStatsOverview;
use App\Filament\Widgets\SchoolSupportOverview;
use App\Filament\Widgets\SuperAdminOverview;
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
        if (auth()->user()?->isSuperAdmin()) {
            return $form->schema([]);
        }

        return $form->schema([
            Select::make('period')->label('Périmètre')->options([
                'today' => 'Aujourd’hui',
                'active_term' => 'Période active',
                'academic_year' => 'Année scolaire active',
                'custom' => 'Période personnalisée',
            ])->default('today')->live(),
            DatePicker::make('date')->label('Date')->default(now()->toDateString()),
            DatePicker::make('from')->label('Du')->visible(fn ($get): bool => $get('period') === 'custom'),
            DatePicker::make('to')->label('Au')->visible(fn ($get): bool => $get('period') === 'custom'),
            Select::make('class_room_id')
                ->label('Classe')
                ->options(fn () => ClassRoom::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->native(false),
        ])->columns(2);
    }

    public function getWidgets(): array
    {
        if (auth()->user()?->isSuperAdmin()) {
            return [SuperAdminOverview::class, SchoolSupportOverview::class];
        }

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
