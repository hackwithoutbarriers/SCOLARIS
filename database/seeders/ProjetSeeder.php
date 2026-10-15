<?php

namespace Database\Seeders;

use App\Models\Projet;
use Illuminate\Database\Seeder;

class ProjetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'titre' => 'Baies vitrées coulissantes – Villa Agoè',
                'categorie' => 'batiment',
                'description' => 'Projet de démonstration à remplacer : fabrication et pose de trois baies vitrées en aluminium avec système coulissant, vitrage sécurité et finition anthracite pour une villa contemporaine à Agoè.',
                'ville' => 'Lomé',
                'pays' => 'Togo',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'TGN-LOM-AGOE-2026-001',
                'images' => [
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'titre' => 'Mur-rideau et gardes-corps – Baguida',
                'categorie' => 'batiment',
                'description' => 'Projet de démonstration à remplacer : mise en œuvre d’un mur-rideau aluminium gris métallisé et de garde-corps sur mesure pour un immeuble tertiaire à forte luminosité naturelle.',
                'ville' => 'Lomé',
                'pays' => 'Togo',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'TGN-LOM-BAG-2026-002',
                'images' => [
                    'https://images.unsplash.com/photo-1511818966892-d7d671e672a2?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'titre' => 'Fenêtres coulissantes – Résidence Kpalimé',
                'categorie' => 'batiment',
                'description' => 'Projet de démonstration à remplacer : rénovation complète d’une résidence avec fenêtres coulissantes aluminium thermolaqué blanc satin, joints de qualité et système anti-souffle adapté au climat côtier.',
                'ville' => 'Kpalimé',
                'pays' => 'Togo',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'TGN-KPA-2026-003',
                'images' => [
                    'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'titre' => 'Cuisine aluminium moderne – Tokoin',
                'categorie' => 'mobilier',
                'description' => 'Projet de démonstration à remplacer : cuisine intégrée sur mesure avec façades en aluminium laqué, plan de travail stratifié, îlot central et rangement optimisé pour un logement urbain moderne.',
                'ville' => 'Lomé',
                'pays' => 'Togo',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'TGN-LOM-TOK-2026-004',
                'images' => [
                    'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'titre' => 'Comptoir commercial aluminium – Adidogomé',
                'categorie' => 'mobilier',
                'description' => 'Projet de démonstration à remplacer : fabrication d’un comptoir de vente et d’accueil sur mesure pour un point de vente, avec matériaux résistants, finition miroir et rangements intégrés.',
                'ville' => 'Lomé',
                'pays' => 'Togo',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'TGN-LOM-ADI-2026-005',
                'images' => [
                    'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'titre' => 'Table haute lounge – Cotonou',
                'categorie' => 'mobilier',
                'description' => 'Projet de démonstration à remplacer : création d’une table haute en aluminium et bois pour un espace de restauration haut de gamme avec finition élégante et structure légère.',
                'ville' => 'Cotonou',
                'pays' => 'Bénin',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'BEN-COT-2026-006',
                'images' => [
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'titre' => 'Portes et vitrines boutique – Adzopé',
                'categorie' => 'batiment',
                'description' => 'Conception et pose d’une vitrine aluminium avec portes battantes, sécurisation renforcée et intégration visuelle premium pour un commerce de proximité.',
                'ville' => 'Abidjan',
                'pays' => 'Côte d’Ivoire',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'CIV-ABJ-2026-007',
                'images' => [
                    'https://images.unsplash.com/photo-1448630360428-65456885c650?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'titre' => 'Clôture et portail aluminium – Tsévié',
                'categorie' => 'batiment',
                'description' => 'Portail automatisé et clôture aluminium avec panneaux ajourés, sécurisation fonctionnelle et esthétique contemporaine pour une propriété familiale.',
                'ville' => 'Tsévié',
                'pays' => 'Togo',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'TGN-TSE-2026-008',
                'images' => [
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'titre' => 'Mezzanine et mobilier bureau – Kara',
                'categorie' => 'mobilier',
                'description' => 'Aménagement de bureau professionnel avec mobilier aluminium sur mesure, cloisonnement léger et rangement intégré pour une meilleure ergonomie.',
                'ville' => 'Kara',
                'pays' => 'Togo',
                'is_visible_public' => true,
                'code_suivi_diaspora' => 'TGN-KAR-2026-009',
                'images' => [
                    'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
        ];

        foreach ($projects as $project) {
            Projet::firstOrCreate(
                ['code_suivi_diaspora' => $project['code_suivi_diaspora']],
                $project,
            );
        }
    }
}
