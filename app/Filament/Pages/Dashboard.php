<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use \Filament\Pages\Dashboard\Concerns\HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('date')->label('Date')->default(now()->toDateString()),
            Select::make('class_room_id')
                ->label('Classe')
                ->options(fn () => \App\Models\ClassRoom::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->native(false),
        ])->columns(2);
    }

    public function getWidgets(): array
    {
        $widgets = [\App\Filament\Widgets\SchoolStatsOverview::class, \App\Filament\Widgets\AcademicOverview::class];

        if (auth()->user()?->isAdmin()) {
            $widgets[] = \App\Filament\Widgets\AttendanceOverview::class;
        }

        return $widgets;
    }
}
