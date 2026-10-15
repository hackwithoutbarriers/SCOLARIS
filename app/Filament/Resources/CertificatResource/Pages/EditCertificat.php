<?php

namespace App\Filament\Resources\CertificatResource\Pages;

use App\Filament\Resources\CertificatResource;
use App\Models\Attestation;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificat extends EditRecord
{
    protected static string $resource = CertificatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('voir_pdf')
                ->label('Voir le PDF')
                ->icon('heroicon-o-eye')
                ->url(fn (Attestation $record): string => route('certificat.pdf', ['attestation' => $record]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
