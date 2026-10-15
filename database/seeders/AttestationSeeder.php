<?php

namespace Database\Seeders;

use App\Models\Attestation;
use Illuminate\Database\Seeder;

class AttestationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $certificates = [
            [
                'apprenti_nom_prenom' => 'Kossi Amégnon',
                'date_debut_apprentissage' => '2024-01-08',
                'date_fin_apprentissage' => '2024-12-20',
                'specialisations' => 'Pose de baies vitrées coulissantes & étanchéité marine, découpes sur mesure, contrôle de nivellement',
                'date_delivrance' => '2025-01-15',
            ],
            [
                'apprenti_nom_prenom' => 'Aya Dossou',
                'date_debut_apprentissage' => '2024-02-05',
                'date_fin_apprentissage' => '2025-01-17',
                'specialisations' => 'Fabrication de mobilier d’intérieur en aluminium, façades laquées, finition et assemblage',
                'date_delivrance' => '2025-02-10',
            ],
            [
                'apprenti_nom_prenom' => 'Sèna Ouro-Bangna',
                'date_debut_apprentissage' => '2023-09-04',
                'date_fin_apprentissage' => '2024-08-30',
                'specialisations' => 'Menuiserie aluminium extérieure, portes et fenêtres, sécurisation et maintient chantier',
                'date_delivrance' => '2024-09-19',
            ],
        ];

        foreach ($certificates as $certificate) {
            Attestation::updateOrCreate(
                [
                    'apprenti_nom_prenom' => $certificate['apprenti_nom_prenom'],
                    'date_debut_apprentissage' => $certificate['date_debut_apprentissage'],
                ],
                $certificate
            );
        }
    }
}
