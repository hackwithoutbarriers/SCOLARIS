<?php

namespace App\Filament\Resources\AttestationResource\Pages;

use App\Filament\Resources\AttestationResource;
use App\Models\Attestation;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttestation extends EditRecord
{
    protected static string $resource = AttestationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('telecharger_pdf')
                ->label('Générer les documents')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (Attestation $record): string => route('documents.links', ['attestation' => $record]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
