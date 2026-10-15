<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicContactRequest;
use App\Http\Requests\StorePublicDevisRequest;
use App\Models\ContactMessage;
use App\Models\Projet;
use App\Models\PublicDevis;
use App\ReviewData;
use App\Services\ContactMessageWhatsAppNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicController extends Controller
{
    public function __construct(private ContactMessageWhatsAppNotifier $whatsappNotifier) {}

    public function home()
    {
        $featuredProjects = collect();
        $reviewsSummary = app(ReviewData::class)->summary();

        if (Schema::hasTable('projets')) {
            $featuredProjects = Projet::query()
                ->where('is_visible_public', true)
                ->latest()
                ->take(3)
                ->get();
        }

        return view('public.home', ['featuredProjects' => $featuredProjects, 'reviewSummary' => $reviewsSummary]);
    }

    public function services()
    {
        return view('public.services', ['serviceCategories' => $this->serviceCategories()]);
    }

    public function service(string $slug)
    {
        $serviceCategories = $this->serviceCategories();
        $service = $serviceCategories[$slug] ?? null;

        abort_if($service === null, 404);

        return view('public.service-detail', ['service' => $service, 'slug' => $slug]);
    }

    public function gallery(Request $request)
    {
        $projects = collect();
        $categories = collect();
        $cities = collect();
        $countries = collect();

        if (Schema::hasTable('projets')) {
            $query = Projet::query()->where('is_visible_public', true);

            if ($request->filled('categorie')) {
                $query->where('categorie', $request->input('categorie'));
            }

            if ($request->filled('ville')) {
                $query->where('ville', $request->input('ville'));
            }

            if ($request->filled('pays')) {
                $query->where('pays', $request->input('pays'));
            }

            $projects = $query->latest()->get();

            $categories = Projet::query()
                ->where('is_visible_public', true)
                ->select('categorie')
                ->distinct()
                ->pluck('categorie')
                ->sort();

            $cities = Projet::query()
                ->where('is_visible_public', true)
                ->select('ville')
                ->distinct()
                ->pluck('ville')
                ->sort();

            $countries = Projet::query()
                ->where('is_visible_public', true)
                ->select('pays')
                ->distinct()
                ->pluck('pays')
                ->sort();
        }

        return view('public.gallery', compact('projects', 'categories', 'cities', 'countries'));
    }

    public function showQuoteForm()
    {
        return view('public.devis', ['quoteConfig' => $this->quoteConfig()]);
    }

    public function storeQuote(StorePublicDevisRequest $request)
    {
        $this->persistQuote($request);

        return redirect()->route('public.devis')->with('success', 'Votre demande de devis a bien été enregistrée. Notre équipe vous répondra très prochainement.');
    }

    public function storeQuoteApi(StorePublicDevisRequest $request)
    {
        $quote = $this->persistQuote($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Votre demande de devis a bien été enregistrée.',
                'numero_demande' => $quote->numero_demande,
            ], 201);
        }

        return redirect()->route('public.devis')->with('success', 'Votre demande de devis a bien été enregistrée. Notre équipe vous répondra très prochainement.');
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function storeContact(StorePublicContactRequest $request)
    {
        $message = ContactMessage::create($request->validated());
        $this->whatsappNotifier->send($message);

        return redirect()->route('contact')->with('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
    }

    private function persistQuote(StorePublicDevisRequest $request): PublicDevis
    {
        $validated = $request->validated();
        $dimensions = is_array($validated['dimensions'] ?? null) ? $validated['dimensions'] : [];
        $options = is_array($validated['options'] ?? null) ? $validated['options'] : [];
        $estimation = $this->calculateEstimation($validated);

        return PublicDevis::create([
            'nom' => $validated['nom'],
            'telephone' => $validated['telephone'],
            'ville' => $validated['ville'] ?? null,
            'pays' => $validated['pays'] ?? 'Togo',
            'description_besoin' => $validated['description_besoin'],
            'categorie' => $validated['categorie'] ?? null,
            'sous_type' => $validated['sous_type'] ?? null,
            'dimensions' => $dimensions,
            'finition' => $validated['finition'] ?? null,
            'vitrage' => $validated['vitrage'] ?? null,
            'options' => $options,
            'estimation_min' => isset($estimation['min']) ? (int) round((float) $estimation['min']) : null,
            'estimation_max' => isset($estimation['max']) ? (int) round((float) $estimation['max']) : null,
            'devise' => $estimation['devise'] ?? 'FCFA',
            'source' => $validated['source'] ?? 'simulateur',
            'statut' => 'nouvelle',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{min: int, max: int, devise: string}
     */
    private function calculateEstimation(array $validated): array
    {
        $subtype = collect(config('pricing.subtypes.'.($validated['categorie'] ?? ''), []))
            ->firstWhere('id', $validated['sous_type'] ?? null);
        $dimensions = is_array($validated['dimensions'] ?? null) ? $validated['dimensions'] : [];

        if ($subtype === null) {
            return ['min' => 0, 'max' => 0, 'devise' => 'FCFA'];
        }

        $quantity = ($subtype['unit'] ?? null) === 'm²'
            ? (float) ($dimensions['largeur'] ?? 0) * (float) ($dimensions['hauteur'] ?? 0)
            : (float) ($dimensions['longueur'] ?? 0);
        $finition = collect(config('pricing.finitions', []))->firstWhere('id', $validated['finition'] ?? null);
        $vitrage = collect(config('pricing.vitrages', []))->firstWhere('id', $validated['vitrage'] ?? null);
        $total = (float) $subtype['base'] * $quantity * (float) ($finition['multiplier'] ?? 1);

        if ($subtype['hasGlazing'] ?? false) {
            $total *= (float) ($vitrage['multiplier'] ?? 1);
        }

        foreach ($validated['options'] ?? [] as $optionId) {
            $option = collect(config('pricing.options', []))->firstWhere('id', $optionId);

            if ($option === null) {
                continue;
            }

            $total += match ($option['type']) {
                'flat' => (float) $option['value'],
                'perUnit' => (float) $option['value'] * $quantity,
                'percent' => $total * (float) $option['value'],
                default => 0,
            };
        }

        return [
            'min' => (int) round($total * 0.85),
            'max' => (int) round($total * 1.15),
            'devise' => 'FCFA',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteConfig(): array
    {
        return config('pricing');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function serviceCategories(): array
    {
        return [
            'menuiserie-aluminium' => [
                'slug' => 'menuiserie-aluminium',
                'title' => 'Menuiserie aluminium',
                'summary' => 'Fenêtres, portes, baies vitrées et façades aluminium conçues pour résister au climat tropical, au sel marin et aux contraintes d’usage quotidien.',
                'highlights' => [
                    'Baies vitrées coulissantes',
                    'Portes-fenêtres et vantaux',
                    'Mur-rideaux et vitrines',
                    'Pose et étanchéité marine',
                ],
                'description' => 'Nous concevons des ouvertures aluminium sur mesure pour les maisons, villas, immeubles et commerces du littoral togolais. Chaque projet est étudié pour allier performance thermique, sécurité, esthétique et facilité d’entretien.',
                'process' => [
                    'Étude du cahier des charges et prise de mesures sur site.',
                    'Conception sur plan avec choix des finitions et accessoires.',
                    'Fabrication en atelier et pose avec contrôle de l’étanchéité.',
                    'Livraison avec conseils d’entretien et suivi post-installation.',
                ],
            ],
            'menuiserie-batiment' => [
                'slug' => 'menuiserie-batiment',
                'title' => 'Menuiserie bâtiment',
                'summary' => 'Construction, rénovation et finitions sur mesure pour les habitations, villas, bureaux et espaces commerciaux de Lomé et de la sous-région.',
                'highlights' => [
                    'Rénovation complète',
                    'Portails et clôtures',
                    'Garde-corps et façades',
                    'Suivi de chantier rigoureux',
                ],
                'description' => 'Nos missions de bâtiment couvrent la conception, la production et l’installation d’éléments architecturaux en aluminium pour les projets résidentiels et tertiaires. Nous travaillons en cohérence avec les exigences de qualité, de délais et de budget du client.',
                'process' => [
                    'Diagnostic du site et consultation avec le maître d’œuvre ou le client.',
                    'Préparation du devis détaillé avec essai d’agencement.',
                    'Fabrication, installation et finition sur chantier.',
                    'Vérification finale et accompagnement avant mise en service.',
                ],
            ],
            'mobilier-sur-mesure' => [
                'slug' => 'mobilier-sur-mesure',
                'title' => 'Mobilier sur mesure',
                'summary' => 'Cuisines, comptoirs, banquettes, rangements et mobiliers commerciaux pensés pour optimiser les espaces et affirmer votre image de marque.',
                'highlights' => [
                    'Cuisines intégrées',
                    'Comptoirs et réception',
                    'Rangements modulaires',
                    'Finitions personnalisées',
                ],
                'description' => 'Nous réalisons des mobiliers sur mesure pour l’habitat, le commerce et les services. Chaque pièce est dimensionnée selon l’espace disponible, les usages quotidiens et l’identité visuelle recherchée par le client.',
                'process' => [
                    'Analyse de l’espace et recommandations d’architecture intérieure.',
                    'Choix des matériaux, couleurs et finitions.',
                    'Fabrication en atelier puis installation sur site.',
                    'Contrôle final, réglage des éléments et conseils d’entretien.',
                ],
            ],
        ];
    }
}
