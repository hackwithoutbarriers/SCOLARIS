<?php

namespace App\Actions;

use App\Models\Attestation;

class GenerateDocumentLinksAction
{
    /**
     * @return array{certificate: string, attestation: string}
     */
    public function handle(Attestation $document): array
    {
        return [
            'certificate' => route('certificat.pdf', ['attestation' => $document]),
            'attestation' => route('attestation.pdf', ['attestation' => $document]),
        ];
    }
}
