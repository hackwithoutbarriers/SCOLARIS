<?php
namespace App\Filament\Resources\TimetableSlotResource\Pages;

use App\Filament\Resources\TimetableSlotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimetableSlots extends ListRecords
{
    protected static string $resource = TimetableSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Créer un créneau')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
