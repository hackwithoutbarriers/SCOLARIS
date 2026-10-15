<?php

namespace App\Filament\Resources\TemoignageResource\Pages;

use App\Filament\Resources\TemoignageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemoignages extends ListRecords
{
    protected static string $resource = TemoignageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
