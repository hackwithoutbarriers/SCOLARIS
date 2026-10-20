<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Filament\Resources\CertificateResource;
use App\Services\CertificateService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCertificate extends CreateRecord
{
    protected static string $resource = CertificateResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CertificateService::class)->prepare($data);
    }
}
