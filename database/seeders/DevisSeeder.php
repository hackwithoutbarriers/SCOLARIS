<?php

namespace Database\Seeders;

use App\Models\Devis;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quotes = [
            [
                'client_nom' => 'Koffi M.',
                'client_telephone' => '+228 91 24 68 10',
                'client_ville' => 'Lomé',
                'client_pays' => 'Togo',
                'description_chantier' => 'Fourniture et pose de 3 baies vitrées coulissantes en aluminium gris anthracite pour une villa R+1 à Agoè.',
                'montant_materiel' => 1850000,
                'montant_main_doeuvre' => 650000,
                'acompte_requis_pourcentage' => 50,
                'statut' => 'envoye',
            ],
            [
                'client_nom' => 'Société SOTUBA',
                'client_telephone' => '+225 07 45 12 88',
                'client_ville' => 'Abidjan',
                'client_pays' => 'Côte d’Ivoire',
                'description_chantier' => 'Mur-rideau aluminium et garde-corps sur mesure pour l’agence commerciale SOTUBA, avec vitrage sécurisé et finition élégante.',
                'montant_materiel' => 3925000,
                'montant_main_doeuvre' => 1280000,
                'acompte_requis_pourcentage' => 40,
                'statut' => 'accepte',
            ],
            [
                'client_nom' => 'Mme Lawson (Diaspora France)',
                'client_telephone' => '+33 6 78 21 44 90',
                'client_ville' => 'Paris',
                'client_pays' => 'France',
                'description_chantier' => 'Projet de rénovation résidentielle à Lomé avec portes-fenêtres aluminium, brises-soleil et mobilier sur mesure pour un séjour en duplex.',
                'montant_materiel' => 2240000,
                'montant_main_doeuvre' => 760000,
                'acompte_requis_pourcentage' => 45,
                'statut' => 'envoye',
            ],
            [
                'client_nom' => 'M. Sèdjro Akom',
                'client_telephone' => '+228 98 31 77 42',
                'client_ville' => 'Kpalimé',
                'client_pays' => 'Togo',
                'description_chantier' => 'Pose de fenêtres coulissantes aluminium blanc satin et portails maçonnés pour une maison familiale avec accès jardin.',
                'montant_materiel' => 1185000,
                'montant_main_doeuvre' => 470000,
                'acompte_requis_pourcentage' => 55,
                'statut' => 'brouillon',
            ],
            [
                'client_nom' => 'M. Yao Tcham',
                'client_telephone' => '+228 90 77 23 01',
                'client_ville' => 'Tsévié',
                'client_pays' => 'Togo',
                'description_chantier' => 'Cuisine intégrée aluminium laqué, îlot central et mobilier de rangement pour un appartement de 96 m².',
                'montant_materiel' => 1620000,
                'montant_main_doeuvre' => 590000,
                'acompte_requis_pourcentage' => 50,
                'statut' => 'accepte',
            ],
        ];

        foreach ($quotes as $quote) {
            $devis = Devis::query()->firstOrNew(
                ['client_nom' => $quote['client_nom'], 'description_chantier' => $quote['description_chantier']],
            );

            $quote['montant_total'] = $quote['montant_materiel'] + $quote['montant_main_doeuvre'];
            $devis->fill($quote);

            if (! $devis->reference_publique) {
                $devis->reference_publique = Devis::reservePublicReference();
                $devis->numero_devis = 'DEV-'.$devis->reference_publique;
            }

            $devis->saveQuietly();

            DB::table('devis_public_references')
                ->where('reference_publique', $devis->reference_publique)
                ->update(['devis_id' => $devis->getKey(), 'updated_at' => now()]);
        }
    }
}
