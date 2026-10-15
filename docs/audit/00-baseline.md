# Phase 0 — État de référence

## Résumé (10 lignes)
1. Socle démarrable : Laravel 12.69.1, Filament 3.3.55, Livewire 3.8.8, PHP 8.5.10 et Node 22.19.0.
2. Le verrouillage Composer est cohérent avec l’environnement ; les migrations et le seed passent en SQLite mémoire.
3. Pint et la compilation Vite passent ; aucun outil PHPStan/Larastan n’est déclaré dans Composer.
4. La suite de tests contient 98 tests : 96 passent et 2 échouent sur des réponses 403 attendues comme 200.
5. L’audit Composer relève deux avis sur `league/commonmark` 2.10.1 ; aucun correctif de dépendance n’a été appliqué.
6. L’audit npm complet signale 3 vulnérabilités (2 critiques, 1 élevée) ; les dépendances npm de production n’en signalent aucune.
7. Le seeder principal crée une seule école et trois rôles ; le seeder de démonstration séparé crée également une seule école.
8. Une classe `DemoDataSeeder` existe sous forme de fichier vide ; aucune preuve de démo multi-écoles complète.
9. Historique, fichiers non suivis, branche dédiée et commits sont invérifiables : le dossier n’a pas de `.git` et Git n’est pas installé/accesssible.
10. Actions prioritaires : rétablir les deux tests, traiter les alertes de dépendances après tests verts, compléter la démo multi-écoles et auditer les autorisations.

## Environnement et démarrage

| Vérification | Résultat | Preuve / remarque |
|---|---|---|
| PHP / Composer | PHP 8.5.10 ; Composer 2.10.3 | `php -v`, `composer --version` |
| Laravel / Filament / Livewire | 12.69.1 / 3.3.55 / 3.8.8 | `php artisan --version`, `composer show` |
| Node / npm | 22.19.0 / 10.9.3 | `node --version`, `npm --version` |
| Dépendances PHP | Verrou vérifié ; rien à installer ou mettre à jour pour satisfaire `composer.lock` | `composer install --dry-run --no-interaction --no-scripts` |
| Migrations et données d’exemple | Réussies sur SQLite `:memory:` avec `APP_ENV=testing` ; base persistante non utilisée | `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `php artisan migrate --seed --force` |
| Compilation front-end | Réussie, 59 modules transformés | `npm run build` |
| Formatage | Réussi, aucun écart Pint | `vendor\bin\pint --test` |
| Installation réellement à froid | Non exécutée : le dossier contient déjà `.env`, `vendor` et `node_modules`. Ne pas lancer la configuration habituelle sans isoler ses effets sur la base locale. | `composer.json` définit `setup` avec `migrate --force` ; les contrôles ont été faits sans lire `.env` ni toucher à la base persistante. |

La configuration de test déclare SQLite mémoire dans [phpunit.xml](../../phpunit.xml). Le fichier `.env` local et le fichier de base `neondb` présents à la racine n’ont pas été ouverts ni modifiés. `.env` est ignoré par [.gitignore](../../.gitignore) ; son statut dans un historique Git reste invérifiable.

## Historique et état des fichiers

| Point | État |
|---|---|
| Historique récent, branches, changements locaux et fichiers non suivis | Impossible à déterminer : `git` n’est pas une commande disponible et `.git` n’existe pas à la racine. Aucun dépôt n’a été initialisé. |
| Branche `reprise/audit-e2e` et commits atomiques | Non créés, conformément à l’accord de poursuivre localement et de laisser les commits à l’utilisateur. |
| Inspection des marqueurs incomplets | Recherche `rg` sur `app`, `routes`, `database` et `tests` : aucun `TODO`, `FIXME`, `dd()` ou `dump()` trouvé. Les correspondances « placeholder » sont des champs d’interface ou une erreur explicite du moteur de modèles de message, pas des marqueurs de débogage. |
| Fichier vide | `database/seeders/DemoDataSeeder.php` mesure 0 octet. |

## Tests, couverture et qualité

| Zone | Résultat |
|---|---|
| Suite PHPUnit | 98 tests, 96 réussis, 2 échoués, 522 assertions, 395,12 s. Sortie de `php artisan test`. |
| Échec académique | `ResponsiveAcademicPagesTest::test_grade_entry_and_import_pages_are_available_at_all_supported_viewports` reçoit 403 sur `/admin/grade-entry`, attendu 200 ([tests/Feature/ResponsiveAcademicPagesTest.php:37](../../tests/Feature/ResponsiveAcademicPagesTest.php)). |
| Échec financier | `ResponsiveAcademicPagesTest::test_finance_pages_are_available_at_all_supported_viewports` reçoit 403 sur `/admin/payments/create`, attendu 200 ([tests/Feature/ResponsiveAcademicPagesTest.php:53](../../tests/Feature/ResponsiveAcademicPagesTest.php)). À trancher dans l’audit ACL : le test ne suffit pas à établir que l’autorisation attendue est métier-correcte. |
| Présence des tests critiques | Des tests dédiés existent pour finance, isolation multi-écoles, affectations d’enseignants, bulletins, invitations et notifications (`tests/Feature`). |
| Volume | 39 fichiers de tests Feature et 1 fichier Unit, soit 98 cas exécutés au total ; pas de mesure de couverture faute de rapport de couverture produit. |
| Analyse statique | Aucun Larastan/PHPStan configuré ou déclaré dans `composer.json`. Pint est présent et passe. |
| Tests navigateur | Aucun Playwright/Dusk déclaré. Le test « responsive » vérifie des réponses HTTP avec un en-tête `Viewport-Width`, pas le rendu réel à différentes largeurs ; un scénario manuel est décrit dans `docs/responsive-validation.md`. |

## Dépendances et mises à jour recommandées

| Écosystème | Constat | Recommandation / risque |
|---|---|---|
| Composer — sécurité | `composer audit` signale deux avis affectant `league/commonmark` 2.10.1 (contournement de la désactivation du HTML brut et déni de service quadratique). Laravel 12.69.1 le requiert indirectement. | Vérifier le correctif minimal résolvant les avis puis relancer toute la suite avant mise à jour. Aucun changement appliqué car la suite de base échoue déjà. |
| Composer — versions | `twilio/sdk` est déclaré avec la contrainte `*` dans [composer.json](../../composer.json:16) ; la version installée est 8.12.1. Pint 1.31.0 et Sail 1.67.0 ont chacun un correctif disponible. Laravel 12 et Filament 3 ont des versions majeures plus récentes listées par `composer outdated`. | Remplacer la contrainte Twilio non bornée et étudier séparément les correctifs mineurs ; les mises à niveau de Laravel/Filament sont majeures et risquées, à planifier après tests. Ne pas mettre à jour en bloc. |
| npm — sécurité | `npm audit` : 3 vulnérabilités au total (2 critiques liées à `shell-quote`/`concurrently`, 1 élevée dans `source-map-js`). `npm audit --omit=dev` : 0 vulnérabilité. | Examiner la résolution de versions de verrouillage (`npm audit fix` est proposé par npm) et revalider build/tests avant toute application. Aucun changement appliqué. |
| npm — versions | `concurrently` 9.2.4 → 9.2.5 disponible ; `vite` 7.3.6 → 7.3.7 ; `laravel-vite-plugin` 2.1.0 → 3.2.0. | Les deux premiers sont des mises à jour mineures/patch à vérifier avec le build ; plugin Vite 3 est une mise à niveau majeure. |

## Données de démonstration

| Seeder | Contenu observé | Écart |
|---|---|---|
| [DatabaseSeeder](../../database/seeders/DatabaseSeeder.php:38) | Une école `DEMO`, une année, un directeur, un enseignant, un comptable, une classe, des élèves/tuteurs, présence, évaluations et paiements. Mot de passe de démonstration codé dans le seeder. | Pas de deuxième école, pas de rôle super administrateur ni de censeur ; noms/matières et terminologie partiellement anglophones. |
| [DemoSchoolSeeder](../../database/seeders/DemoSchoolSeeder.php:25) | Une école `DEMO-TOGO`, 500 élèves annoncés, direction, secrétariat, caisse, enseignants, présences, certificats, emploi du temps et finance. Refus explicite en environnement `production`. | Seeder séparé, non équivalent à une démo multi-écoles ; aucun compte censeur. |
| `DemoDataSeeder.php` | Fichier vide. | À supprimer ou implémenter seulement après décision sur le périmètre et usage des seeders. |

Le README mentionne des identifiants de démonstration et le message du seeder fournit un mot de passe commun de développement. Ces comptes ne doivent pas être utilisés en production ; le blueprint Render ne lance pas les seeders de démo.

## Cartographie applicative

| Composant | Inventaire |
|---|---|
| Modèles | 51 fichiers sous `app/Models`, incluant notamment élèves/tuteurs/inscriptions, années/périodes/classes/matières/affectations/notes/bulletins, paiements/factures/reçus/reversals, dépenses/clôtures, notifications, attestations, abonnements et utilisateurs. |
| Migrations | 63 fichiers sous `database/migrations` ; migrations récentes jusqu’au 11 septembre 2026. |
| Filament | 24 ressources, 10 pages et 7 widgets sous `app/Filament`. |
| Autorisation | 8 policies recensées sous `app/Policies`, plus scopes Eloquent et règles d’accès Filament. |
| Services | 47 fichiers sous `app/Services`, dont services académiques, financiers, notifications, inscriptions, import, reçus, attestations et intervention super-administrateur. |
| HTTP | 11 contrôleurs recensés ; routes web et API regroupées dans `routes/web.php`. Familles : présence/notes/paiements, inscriptions et invitations, bulletins, reçus, cartes d’élève, registre CSV/PDF et notifications signées. |
| Commandes et planification | 6 commandes dans `app/Console/Commands`. [routes/console.php](../../routes/console.php) planifie les relances à 05:00 et 05:05 et la distribution des notifications chaque minute. |
| File, courriels et notifications | 1 job en file, 2 mailables, 3 canaux WhatsApp (Click-to-Chat, Meta Cloud et Twilio) et un canal email. `render.yaml` définit les services web, worker et cron Render. |

## Blocages de départ et suite proposée

1. Créer/restaurer un checkout Git et rendre Git accessible avant de demander une branche ou des commits ; jusque-là, aucune comparaison fiable aux changements locaux ou à l’historique n’est possible.
2. Examiner et résoudre les deux 403 avec la matrice ACL, sans transformer automatiquement les attentes du test en nouvelles permissions.
3. Établir un état vert avant de traiter les mises à jour de sécurité Composer/npm.
4. Auditer la démo et la fixture multi-écoles avant d’utiliser les données de démonstration comme preuve d’isolation.
5. La configuration de production Render force `APP_DEBUG=false`, cookies de session sécurisés, worker et planificateur ; les sauvegardes/restaurations restent du ressort du fournisseur et doivent être prouvées en préproduction.
6. Les valeurs réglementaires, rôles et politiques super-admin restent des décisions produit ; aucun changement métier n’est introduit dans cette phase.
