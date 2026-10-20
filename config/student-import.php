<?php

return [
    'disk' => env('STUDENT_IMPORT_DISK', env('FILESYSTEM_DISK', 'local')),
    // [DÉFAUT À VALIDER] Limite anti-abus par utilisateur, sans bloquer la reprise du même lot.
    'uploads_per_hour' => (int) env('STUDENT_IMPORT_UPLOADS_PER_HOUR', 10),
    // [DÉFAUT À VALIDER] Plafond provisoire pour tenir les imports sans dépendre d'un worker.
    'max_rows' => (int) env('STUDENT_IMPORT_MAX_ROWS', 2000),
    'chunk_size' => (int) env('STUDENT_IMPORT_CHUNK_SIZE', 100),
    'max_file_size' => (int) env('STUDENT_IMPORT_MAX_FILE_SIZE', 5 * 1024 * 1024),
    // [DÉFAUT À VALIDER] Durée choisie par l'utilisateur, à faire confirmer juridiquement.
    'retention_days' => (int) env('STUDENT_IMPORT_RETENTION_DAYS', 30),

    'synonyms' => [
        'student_number' => ['matricule', 'n° matricule', 'numero matricule', 'numéro matricule', 'admission number'],
        'last_name' => ['nom', 'nom de l’élève', 'nom de l\'élève', 'nom eleve', 'nom de famille'],
        'first_name' => ['prénom', 'prenom', 'prénom(s)', 'prenoms', 'first name'],
        'middle_name' => ['deuxième prénom', 'second prénom', 'middle name'],
        'date_of_birth' => ['date de naissance', 'né(e) le', 'né le', 'nee le', 'date naissance'],
        'gender' => ['sexe', 'genre'],
        'class' => ['classe', 'niveau', 'class'],
        'guardian_first_name' => ['prénom du responsable', 'prenom responsable', 'prénom tuteur', 'prenom tuteur'],
        'guardian_last_name' => ['nom du responsable', 'nom responsable', 'nom tuteur'],
        'guardian_name' => ['responsable', 'responsables', 'nom du tuteur', 'nom du parent', 'contact tuteur'],
        'guardian_phone' => ['téléphone parent', 'telephone parent', 'tél parent', 'tel parent', 'contact parent', 'contact responsable', 'téléphone tuteur', 'telephone tuteur'],
    ],
];
