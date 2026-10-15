<?php

namespace App\Filament\Resources\PublicDevisResource\Pages;

use App\Filament\Resources\PublicDevisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPublicDevis extends ListRecords
{
    protected static string $resource = PublicDevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
