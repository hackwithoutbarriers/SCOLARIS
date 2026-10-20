# IDEA-ONB-01 — Rapport de mesure intermédiaire

## Mesures exécutées

État documentaire mis à jour le **20 octobre 2026**. Les résultats ci-dessous distinguent le dernier test ciblé de la baseline complète précédente; ils ne constituent pas une mesure de production.

- Dernier test ciblé de la page Import + activation : `php artisan test --compact tests/Feature/StudentImportPageTest.php` — **6 tests, 14 assertions**, verts.
- Tests du module lors de la dernière vérification ciblée : `php artisan test --compact --filter=StudentImport` — **29 tests, 112 assertions**, verts.
- Baseline complète précédente : `php artisan test --no-coverage` — **165 tests, 1 081 assertions**, verts avant les dernières améliorations de reprise/checklist; la suite complète n’a pas été relancée après celles-ci.
- Test de navigation/autorisation : `php artisan test --compact tests/Feature/RoleNavigationVisibilityTest.php` — **6 tests, 292 assertions**, verts.
- Test de volume ajouté : création de **2 000 lignes synthétiques**, analyse du CSV puis simulation et staging des lignes, avec assertion que le traitement complet du test ne dépasse pas 120 secondes.
- Exécution locale observée du test de volume : **38,353 s** pour le processus PHPUnit complet, **66 Mo** de mémoire reportés par PHPUnit pour le processus complet. Ces valeurs incluent bootstrap et base SQLite; elles ne sont pas un benchmark isolé ni une mesure de production.
- Le test mesure simulation/staging, **pas** l’écriture de 2 000 inscriptions métier ni les performances PostgreSQL/S3. Le débit de traitement par action de 100 lignes n’a pas encore été mesuré séparément.

## Parcours E2E réalisé

Un test Filament automatisé couvre un directeur qui téléverse un CSV synthétique, choisit une année et une classe, analyse, simule et traite un lot; il vérifie la création de l’élève et de l’inscription. Le test couvre également le téléchargement du modèle CSV synthétique.

Ce parcours ne constitue pas encore le parcours métier complet demandé : l’onboarding réel, les corrections depuis l’interface, les doublons, la première facture, le premier encaissement, la première présence et un chronométrage avec un utilisateur d’école ne sont pas tous exécutés de bout en bout. Aucun clic utilisateur n’a été chronométré.

## Améliorations avant présentation pilote

- Un lot actif est rouvert automatiquement à l’entrée dans l’import; le mapping, l’aperçu et les compteurs de simulation sont restaurés. La checklist d’activation pointe vers le lot à reprendre.
- La checklist affiche une seule prochaine action directe et confirme la présence des cinq éléments de démarrage quand ils sont tous configurés. Cela ne remplace pas la validation de l’opérationnalité définie par la fiche.
- La démonstration est documentée avec les fixtures synthétiques; le parcours pilote reste distinct et requiert un fichier anonymisé autorisé.
- Tests ciblés couvrent la restauration de lot et la recommandation de checklist. Le chronométrage humain et l’E2E métier complet restent à faire avec l’école pilote.

## Résultats sur fixtures

Les fichiers `tests/Fixtures/onb01/` sont exclusivement synthétiques et couvrent maternelle, primaire, collège et lycée. Le test d’acceptation du service confirme :

| Fixture | Lignes analysées | Prêtes | Avertissements | Erreurs | Doublons | Ignorées |
|---|---:|---:|---:|---:|---:|---:|
| `01_cycles_complets.csv` | 28 | 20 | 4 | 5 | 2 | 1 |
| `03_entete_decale_et_conflits.csv` | 9 | 4 | 3 | 3 | 2 | 0 |

Les catégories de compteurs peuvent se chevaucher (avertissement sur une ligne en revue). Ces résultats sont des contrats synthétiques, non un taux de validité réel; le CSV Windows-1252 et le XLSX sont volontairement refusés en MVP.

## Avant activation terrain

- Obtenir un fichier anonymisé d’une école couvrant les cycles réellement utilisés.
- Valider en priorité les dates à deux chiffres, les numéros multiples, les responsables en colonne combinée, les matricules et les libellés de classe.
- Mesurer l’analyse, la simulation et chaque lot de 100 inscriptions sur PostgreSQL et le disque persistant de production, sans enregistrer de valeur personnelle dans les métriques.
- Comparer les compteurs et un échantillon de dossiers créés à la source, faire signer la réconciliation et confirmer le comportement facture/inscription.
- Faire relire durées et finalité par un juriste et valider les conditions de traitement par l’IPDCP.

**Décision** : ne pas déclarer le KPI « 2 000 élèves en moins d’une journée » atteint et ne pas lancer de fichier réel avant cette validation pilote.
