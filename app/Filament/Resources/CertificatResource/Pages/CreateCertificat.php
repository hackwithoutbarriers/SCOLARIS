<?php

namespace App\Filament\Resources\CertificatResource\Pages;

use App\Filament\Resources\CertificatResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Kept for compatibility with previously cached Filament panel routes.
 * Certificate creation is now exposed through AttestationResource.
 */
class CreateCertificat extends CreateRecord
{
    protected static string $resource = CertificatResource::class;
}
