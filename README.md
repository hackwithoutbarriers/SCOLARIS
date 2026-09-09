# Scolaris v1.0.0

Socle SaaS multi-écoles pour la gestion administrative des établissements privés.

## Stack

PHP 8.2+, Laravel 12, Filament 3, Eloquent, PostgreSQL (SQLite possible pour les tests), Vite/Tailwind.

## Installation

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Pour le développement rapide, SQLite est accepté. La validation de production doit utiliser PostgreSQL avec l'extension PHP `pdo_pgsql` activée :

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=Scolaris
DB_USERNAME=postgres
DB_PASSWORD=...
DB_SSLMODE=require
DB_NEON_POOLER=true
```

Pour Neon, utilisez le host `ep-...-pooler...neon.tech`, le nom de base `neondb`, l'utilisateur fourni par Neon et `DB_SSLMODE=require`. `DB_NEON_POOLER=true` désactive les transactions englobant les migrations DDL, nécessaires avec le pooler Neon.

Créer une base vide, puis exécuter `php artisan migrate --force` et `php artisan db:seed --force`. Ne jamais versionner `.env` ni les secrets PostgreSQL. Les tests automatisés continuent d'utiliser SQLite en mémoire via `phpunit.xml`.

## Démonstration

Après `php artisan db:seed` :

- Super Admin : `admin@example.com` / `password` (développement local uniquement)
- Directeur démo : `director@example.com` / `password`

Ouvrir `/admin`. Le Super Admin voit toutes les écoles et utilisateurs. Les comptes rattachés à une école sont isolés par le scope Eloquent et les policies.

## Fonctionnalités Sprint 1

Écoles, utilisateurs/RBAC (`super_admin`, `director`, `teacher`, `accountant`), années et périodes, classes, matières, affectations, élèves, responsables, inscriptions, import CSV transactionnel, audit et dashboard Filament.

Import :

```bash
php artisan students:import docs/examples/students-import.csv --school=1
```

Le format de référence est [docs/examples/students-import.csv](docs/examples/students-import.csv).

## Architecture

Le métier est dans Laravel. Les modèles métier utilisent `BelongsToSchool` et `SchoolScope`; un Super Admin n'a pas de tenant fixe. Les policies refusent les accès inter-écoles et les opérations d'un enseignant/comptable sur les référentiels administratifs. Les activations d'années sont transactionnelles via `AcademicYearService`.

## Présences et communication parentale

Le parcours enseignant est disponible sur `/teacher/attendance` après connexion. Les classes sont limitées aux affectations de l'enseignant. Les statuts `PRESENT`, `LATE`, `ABSENT` et `EXCUSED` sont enregistrés dans une session, puis verrouillés à la validation. Une correction après validation est réservée aux utilisateurs administratifs et est auditée.

Le détail du modèle, de la synchronisation hors ligne, de la policy de notification et du worker est documenté dans [docs/attendance.md](docs/attendance.md).

Voir [docs/architecture.md](docs/architecture.md).

## Commandes utiles

```bash
php artisan test
php artisan migrate:fresh --seed
php artisan queue:work
php artisan schedule:work
php artisan route:list
```

## Production Deployment — Render

Le déploiement de référence utilise le [Dockerfile](Dockerfile) et
le blueprint [render.yaml](render.yaml). Render doit fournir une
base PostgreSQL managée et les variables marquées `sync: false` dans le
blueprint. Ne placez jamais de secret dans le dépôt.

Le conteneur construit les assets Vite, installe les dépendances PHP, crée le
lien de stockage, exécute les migrations et démarre Apache sur `PORT`. Le
healthcheck est `GET /health`. Après déploiement, vérifier `/health`, `/login`,
la connexion, puis le scénario de [smoke test](docs/production-checklist.md).

Les fichiers utilisateurs et les PDF ne doivent pas être stockés sur le disque
éphémère d'un service Render. Configurer `FILESYSTEM_DISK=s3` avec un bucket
persistant et ses variables `AWS_*` avant d'activer les uploads en production.
La base PostgreSQL doit avoir les sauvegardes et la restauration gérées par
Render ou le fournisseur choisi; l'application ne prétend pas les créer.

`QUEUE_CONNECTION=database` est fourni par le worker Render exécutant le script
embarqué `/usr/local/bin/start-worker.sh` si les notifications
ou les traitements financiers asynchrones sont activés. Le blueprint fournit
également un cron quotidien à 05:00 UTC pour `php artisan schedule:run`, qui
déclenche les relances de recouvrement idempotentes via
`/usr/local/bin/run-scheduler.sh`.

### Render sans CLI

Dans le Dashboard Render, créer les services depuis `render.yaml` (New +
Blueprint) ou créer manuellement un Web Service, un Background Worker et un
Cron Job à partir de la même image Docker. Aucun `composer`, `npm`, `migrate`,
`storage:link` ou `optimize` manuel n'est requis : le Dockerfile construit les
assets et les dépendances, puis l'entrypoint exécute automatiquement
`storage:link`, `migrate --force` et `optimize` au démarrage.

- Web Service : Dockerfile par défaut (`CMD ["apache2-foreground"]`), port
  `10000`, healthcheck `/health`.
- Background Worker : commande
  `/usr/local/bin/start-worker.sh`.
- Cron Job : commande
  `/usr/local/bin/run-scheduler.sh`, plan `0 5 * * *`.

Le worker et le cron doivent partager exactement les mêmes variables
PostgreSQL, `APP_KEY` et configuration de queue que le Web Service.

Voir [docs/deployment-files.md](docs/deployment-files.md) pour l'inventaire
exhaustif des fichiers et dossiers à publier.

### Variables obligatoires

`APP_KEY`, `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` et `DB_PASSWORD`
sont obligatoires en production. `MAIL_*`, `SMS_*`, `AWS_*`, et
`PAYMENT_WEBHOOK_SECRET` sont optionnelles, mais une intégration externe reste
non configurée tant que ses identifiants réels et un test de bout en bout ne
sont pas disponibles.

Voir [docs/production-checklist.md](docs/production-checklist.md) pour la
validation opérationnelle, les rôles, la sécurité, les sauvegardes et les smoke
tests.

## Validation Sprint 2.1

La suite complète couvre le workflow présence, l'isolation IDOR inter-écoles et le retry HTTP du provider :

```bash
php artisan test
php artisan test tests/Feature/TenantIsolationAttendanceTest.php
php artisan test tests/Feature/NotificationProviderRetryTest.php
```

Le dashboard Filament du Directeur expose les filtres date/classe et les statistiques présence. Le test navigateur hors ligne se fait sur `/teacher/attendance` : charger la classe, couper le réseau, modifier, recharger, puis rétablir le réseau et vérifier la synchronisation.
