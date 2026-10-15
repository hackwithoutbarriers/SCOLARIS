<?php

namespace App\Filament\Resources\CertificatResource\Pages;

use App\Filament\Resources\CertificatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCertificats extends ListRecords
{
    protected static string $resource = CertificatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
