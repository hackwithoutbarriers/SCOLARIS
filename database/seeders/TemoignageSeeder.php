<?php

namespace Database\Seeders;

use App\Models\Temoignage;
use Illuminate\Database\Seeder;

class TemoignageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'nom_client' => 'Kokou A.',
                'ville' => 'Kara, Togo',
                'projet_type' => 'mobilier',
                'projet_ref' => 'DEV-00001',
                'note' => 5,
                'texte' => 'Le mobilier sur mesure a transformé notre salon. La finition est impeccable, l’équipe a été très professionnelle et le suivi a été clair de bout en bout.',
                'photo_projet' => '/uploads/temoignages/tm_001.jpg',
                'date_projet' => '2026-03-14',
                'consentement' => true,
                'statut' => 'publie',
                'source' => 'interne',
            ],
            [
                'nom_client' => 'Nadia B.',
                'ville' => 'Lomé, Togo',
                'projet_type' => 'baie vitrée',
                'projet_ref' => 'DEV-00012',
                'note' => 5,
                'texte' => 'Nous avons commandé une baie vitrée pour notre maison. Le rendu est magnifique, la pose a été propre et le service client était très réactif. Je recommande sans hésiter.',
                'photo_projet' => '/uploads/temoignages/tm_002.jpg',
                'date_projet' => '2026-05-20',
                'consentement' => true,
                'statut' => 'publie',
                'source' => 'interne',
            ],
            [
                'nom_client' => 'M. Kossi T.',
                'ville' => 'Atakpamé, Togo',
                'projet_type' => 'cuisine',
                'projet_ref' => 'DEV-00018',
                'note' => 4,
                'texte' => 'Très bon rapport qualité-prix. L’équipe nous a aidés à choisir la bonne finition et la cuisine est fonctionnelle, moderne et bien pensée pour notre quotidien.',
                'photo_projet' => '/uploads/temoignages/tm_003.jpg',
                'date_projet' => '2026-06-10',
                'consentement' => true,
                'statut' => 'publie',
                'source' => 'interne',
            ],
        ];

        foreach ($items as $item) {
            Temoignage::updateOrCreate(
                ['projet_ref' => $item['projet_ref'], 'nom_client' => $item['nom_client']],
                $item
            );
        }
    }
}
