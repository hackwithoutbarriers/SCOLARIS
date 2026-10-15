<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemoignageRequest;
use App\Models\Devis;
use App\Models\Temoignage;
use App\ReviewData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewData $reviewData) {}

    public function index()
    {
        return view('public.reviews', [
            'summary' => $this->reviewData->summary(),
            'reviews' => $this->reviewData->mergedReviews(),
        ]);
    }

    public function shareForm(Request $request)
    {
        return view('public.review-share', [
            'prefillName' => $request->query('client', ''),
            'prefillProjectRef' => $request->query('projet_ref', ''),
        ]);
    }

    public function storeShare(StoreTemoignageRequest $request)
    {
        $validated = $request->validated();
        $relativePath = null;

        if ($request->hasFile('photo_projet')) {
            $relativePath = $request->file('photo_projet')->store(
                'temoignages',
                config('filesystems.default'),
            );
        }

        $devis = null;
        if (! empty($validated['projet_ref'])) {
            $devis = Devis::query()
                ->where('numero_devis', $validated['projet_ref'])
                ->orWhere('reference_publique', $validated['projet_ref'])
                ->first();
        }

        $temoignage = Temoignage::create([
            'devis_id' => $devis?->id,
            'nom_client' => $validated['nom_client'],
            'ville' => $validated['ville'] ?? null,
            'projet_type' => $validated['projet_type'] ?? null,
            'projet_ref' => $validated['projet_ref'] ?? null,
            'note' => (int) $validated['note'],
            'texte' => $validated['texte'],
            'photo_projet' => $relativePath,
            'date_projet' => $validated['date_projet'] ?? now()->toDateString(),
            'consentement' => true,
            'statut' => 'en_attente',
            'source' => 'interne',
        ]);

        if ($devis) {
            $devis->update(['statut' => 'livre']);
        }

        return redirect()->route('reviews')->with('success', 'Merci ! Votre témoignage a bien été reçu et est en attente de validation.');
    }

    public function api(): JsonResponse
    {
        return response()->json([
            'summary' => $this->reviewData->summary(),
            'reviews' => $this->reviewData->mergedReviews(),
        ]);
    }
}
