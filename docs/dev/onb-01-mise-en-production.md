# IDEA-ONB-01 — Checklist de mise en production

Ne pas activer les imports de données réelles tant que chaque point requis n’est pas confirmé avec l’école pilote et l’équipe d’exploitation.

## Stockage et conservation

- [ ] `STUDENT_IMPORT_DISK` pointe vers un disque privé persistant et partagé entre Web Service et Cron Job; Render doit utiliser le bucket S3 privé.
- [ ] `AWS_*`, la région, les permissions minimales et la configuration de chiffrement sont identiques sur le Web Service et les processus planifiés.
- [ ] Le bucket refuse l’accès public; aucun `storage:link` ni URL publique n’expose les imports.
- [ ] Une lecture, une suppression et une restauration contrôlée sont testées avec le compte de service de production.
- [ ] `STUDENT_IMPORT_RETENTION_DAYS` est approuvé par le responsable de traitement et le juriste; la valeur technique par défaut est 30 jours.
- [ ] Le cron `schedule:run` est actif et la commande `student-imports:purge` s’exécute quotidiennement; vérifier ses journaux agrégés et les suppressions du bucket.
- [ ] La purge des fichiers importés, des lignes staging et des copies de sauvegarde est testée. Les dossiers d’élèves créés restent soumis aux workflows métier.

## Plafonds et exploitation

- [ ] Confirmer `STUDENT_IMPORT_MAX_FILE_SIZE` (5 Mo par défaut), `STUDENT_IMPORT_MAX_ROWS` (2 000), `STUDENT_IMPORT_CHUNK_SIZE` (100) et `STUDENT_IMPORT_UPLOADS_PER_HOUR` (10).
- [ ] Vérifier les limites du reverse proxy, PHP/Livewire, du fournisseur de stockage et du timeout HTTP pour l’upload et une action de lot.
- [ ] Vérifier que les imports sont exécutés de façon synchrone par actions reprenables; aucun worker de queue n’est nécessaire pour le MVP.
- [ ] Vérifier que le taux horaire est appliqué par école/utilisateur et que la configuration du cache n’affaiblit pas cette limite entre instances.
- [ ] Examiner les alertes sans exporter de nom, téléphone, ligne CSV ou contenu de staging dans les journaux.

## Données, ACL et reprise

- [ ] Appliquer les migrations après revue, sauvegarde et vérification de la base cible; ne jamais utiliser `migrate:fresh` sur des données existantes.
- [ ] Vérifier les rôles Directeur/Secrétaire, le refus Comptable/Enseignant et les interventions super-admin temporaires et journalisées.
- [ ] Tester sur un tenant pilote l’IDOR de lot, ligne et rapport, le disque privé, l’empreinte du fichier, la reprise d’un lot et la collision de matricule.
- [ ] Confirmer le comportement de `Enrollment::created` et d’une éventuelle facture avec la grille tarifaire de l’école.
- [ ] Vérifier l’échantillon de réconciliation signé par le secrétariat; aucun doublon ne doit être fusionné sans décision humaine.
- [ ] Obtenir une validation juridique écrite de la finalité, de l’information des responsables, des durées, des sauvegardes et des transferts.

## Avant le premier lot réel

- [ ] Partir d’un fichier anonymisé pilote, contrôler l’encodage et les colonnes, puis faire une simulation sans écriture.
- [ ] Sauvegarder la base et tester la restauration avant toute inscription réelle.
- [ ] Obtenir la confirmation explicite du directeur sur l’année, la classe, les matricules et le nombre de créations/mises à jour/erreurs.
- [ ] Garder un responsable disponible pendant le traitement et contrôler un échantillon d’inscriptions après import.
- [ ] Suspendre les imports suivants jusqu’à validation des règles de téléphone, date, doublons et responsables.
