<?php

namespace App\Filament\Resources\AttestationResource\Pages;

use App\Filament\Resources\AttestationResource;
use App\Models\Attestation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAttestation extends CreateRecord
{
    protected static string $resource = AttestationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        if (($this->data['document_mode'] ?? 'nouveau') === 'duplicata') {
            return Attestation::query()->findOrFail((int) $this->data['source_document_id']);
        }

        $data['type_document'] = 'certificat';

        return Attestation::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return route('documents.links', ['attestation' => $this->record]);
    }
}
