<?php

namespace App\Http\Controllers;

use App\Actions\GenerateDocumentLinksAction;
use App\Models\Attestation;
use App\Models\Devis;
use App\Models\User;
use App\Services\PdfDocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    public function __construct(protected PdfDocumentService $pdfDocumentService) {}

    public function documentLinks(Attestation $attestation, GenerateDocumentLinksAction $linksAction): View
    {
        $this->ensureApprovedUser();

        return view('pdf.document-links', [
            'attestation' => $attestation,
            'links' => $linksAction->handle($attestation),
        ]);
    }

    public function devis(Devis $devis): Response
    {
        $this->ensureApprovedUser();

        return $this->pdfDocumentService->downloadView(
            'pdf.devis',
            ['devis' => $devis],
            'devis-'.Str::slug($devis->numero_devis).'.pdf',
            'a4',
            'portrait',
            'devis-'.$devis->getKey().'-'.($devis->updated_at?->getTimestamp() ?? 0)
        );
    }

    public function certificat(Attestation $attestation): Response
    {
        $this->ensureApprovedUser();

        return $this->pdfDocumentService->downloadView(
            'pdf.certificat',
            [
                'attestation' => $attestation,
                'serialNumber' => $attestation->documentNumber('CERT'),
            ],
            'certificat-'.Str::slug($attestation->documentNumber('CERT')).'.pdf',
            'a4',
            'landscape',
            'certificat-'.$attestation->getKey().'-'.($attestation->updated_at?->getTimestamp() ?? 0)
        );
    }

    public function attestation(Attestation $attestation): Response
    {
        $this->ensureApprovedUser();

        return $this->pdfDocumentService->downloadView(
            'pdf.attestation',
            [
                'attestation' => $attestation,
                'serialNumber' => $attestation->documentNumber('ATT'),
            ],
            'attestation-'.Str::slug($attestation->documentNumber('ATT')).'.pdf',
            'a4',
            'portrait',
            'attestation-'.$attestation->getKey().'-'.($attestation->updated_at?->getTimestamp() ?? 0)
        );
    }

    private function ensureApprovedUser(): void
    {
        abort_unless(auth()->user() instanceof User && auth()->user()->isApproved(), 403);
    }
}
