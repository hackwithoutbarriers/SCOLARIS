# Inventaire de déploiement Scolaris

Le dépôt à publier doit contenir les éléments suivants. Les fichiers exclus par
`.gitignore` et `.dockerignore` ne doivent pas être ajoutés au dépôt.

## Racine

- `artisan`
- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `vite.config.js`
- `Dockerfile`
- `.dockerignore`
- `render.yaml`
- `.env.example`
- `.gitignore`
- `.gitattributes`
- `phpunit.xml`
- `VERSION`
- `README.md`

## Application Laravel

- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `public/`
- `resources/`
- `routes/`
- `storage/`

Dans `storage/`, conserver les fichiers `.gitignore` nécessaires aux dossiers
framework et logs. Les données persistantes de production ne doivent pas
dépendre du disque local du conteneur.

## Docker

- `docker/entrypoint.sh`
- `docker/start-worker.sh`
- `docker/run-scheduler.sh`

Le Dockerfile installe Composer en production, compile Vite, installe
`pdo_pgsql`, configure Apache, crée le lien storage, applique les migrations,
optimise Laravel et démarre le processus demandé par Render.

## Documentation et exemples

- `docs/production-checklist.md`
- `docs/architecture.md`
- `docs/attendance.md`
- `docs/collections.md`
- `docs/finance.md`
- `docs/payments.md`
- `docs/payment-providers.md`
- `docs/grade-calculation.md`
- `docs/grade-entry.md`
- `docs/report-card-templates.md`
- `docs/responsive-validation.md`
- `docs/examples/`

The administrative bootstrap command is included in
`app/Console/Commands/CreateSuperAdminCommand.php`.

Les autres documents présents sous `docs/` peuvent également être publiés; ils
ne sont pas requis par le runtime.

## À ne pas déployer / committer

- `.env` et tout fichier `.env.*` contenant des valeurs réelles
- `vendor/`
- `node_modules/`
- `public/build/` (reconstruit dans Docker)
- `public/storage` (lien généré au démarrage)
- `storage/logs/*.log`
- `database/database.sqlite`
- `neondb`
- `.phpunit.result.cache`
- dumps SQL, tokens, clés API, mots de passe et credentials

## Variables Render obligatoires

`APP_KEY`, `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`,
`DB_PASSWORD`, `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`,
`APP_ENV=production`, `APP_DEBUG=false`, `APP_NAME=Scolaris`,
`SESSION_DRIVER=database`, `CACHE_STORE=database` et
`QUEUE_CONNECTION=database`.

Pour le SaaS owner, définir `SCOLARIS_OWNER_EMAIL` et
`SCOLARIS_OWNER_PASSWORD` comme variables Render secrètes. `SCOLARIS_OWNER_NAME`
est optionnelle; à défaut, le nom est dérivé de l'adresse email. L'entrypoint crée ce compte automatiquement. Ne jamais placer ces
valeurs dans `.env.example` avec des valeurs réelles.

Pour `FILESYSTEM_DISK=s3`, ajouter les variables `AWS_*` et vérifier le bucket
avant toute mise en production de fichiers ou de PDF persistants.
