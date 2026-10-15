<?php

return [
    'steps' => ['Catégorie', 'Type de projet', 'Dimensions', 'Finitions & options', 'Coordonnées'],
    'categories' => [
        ['id' => 'batiment', 'label' => 'Bâtiment', 'description' => 'Portes, fenêtres, baies vitrées, garde-corps et façades.'],
        ['id' => 'mobilier', 'label' => 'Mobilier', 'description' => 'Cuisines, comptoirs, armoires et rangements sur mesure.'],
    ],
    'subtypes' => [
        'batiment' => [
            ['id' => 'baie-vitree', 'label' => 'Baie vitrée', 'unit' => 'm²', 'base' => 45000, 'hasGlazing' => true],
            ['id' => 'porte', 'label' => 'Porte', 'unit' => 'm²', 'base' => 38000, 'hasGlazing' => true],
            ['id' => 'fenetre', 'label' => 'Fenêtre', 'unit' => 'm²', 'base' => 32000, 'hasGlazing' => true],
            ['id' => 'garde-corps', 'label' => 'Garde-corps', 'unit' => 'ml', 'base' => 28000, 'hasGlazing' => false],
            ['id' => 'facade', 'label' => 'Façade / mur-rideau', 'unit' => 'm²', 'base' => 52000, 'hasGlazing' => false],
        ],
        'mobilier' => [
            ['id' => 'cuisine', 'label' => 'Cuisine intégrée', 'unit' => 'ml', 'base' => 65000, 'hasGlazing' => false],
            ['id' => 'comptoir', 'label' => 'Comptoir de vente', 'unit' => 'ml', 'base' => 48000, 'hasGlazing' => false],
            ['id' => 'armoire', 'label' => 'Armoire / rangement', 'unit' => 'm²', 'base' => 40000, 'hasGlazing' => false],
        ],
    ],
    'finitions' => [
        ['id' => 'standard', 'label' => 'Aluminium standard', 'multiplier' => 1],
        ['id' => 'ral', 'label' => 'Laquage RAL sur mesure', 'multiplier' => 1.15],
        ['id' => 'bois', 'label' => 'Effet bois (thermolaquage)', 'multiplier' => 1.25],
    ],
    'vitrages' => [
        ['id' => 'simple', 'label' => 'Vitrage simple', 'multiplier' => 1],
        ['id' => 'double', 'label' => 'Double vitrage', 'multiplier' => 1.2],
        ['id' => 'securit', 'label' => 'Verre sécurit', 'multiplier' => 1.35],
    ],
    'options' => [
        ['id' => 'moustiquaire', 'label' => 'Moustiquaire intégrée', 'type' => 'perUnit', 'value' => 8000],
        ['id' => 'motorisation', 'label' => 'Motorisation', 'type' => 'flat', 'value' => 150000],
        ['id' => 'renfort', 'label' => 'Quincaillerie renforcée (climat côtier)', 'type' => 'percent', 'value' => 0.12],
    ],
];
