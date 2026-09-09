# Présences et communication parentale

## Modèle

`attendance_sessions` appartient à une école, une année scolaire, une classe et un enseignant. Une session passe de `DRAFT`/`OPEN` à `VALIDATED` ou `CANCELLED`. `attendance_records` contient un statut par élève inscrit dans la classe et l'année concernées.

Les modèles utilisent `BelongsToSchool`; les contrôleurs ne font jamais confiance au `school_id` fourni par le client.

## Workflow

1. L'enseignant ouvre `/teacher/attendance`.
2. Il choisit une classe affectée et marque chaque élève.
3. `Enregistrer` crée la session et synchronise les opérations.
4. La session est validée par l'API `/api/attendance/sessions/{id}/validate`.
5. Les absences validées et les retards dépassant le seuil alimentent `notification_queue`.

## Hors ligne et synchronisation

La page mobile conserve uniquement les statuts, l'identifiant de session et les identifiants d'opération dans `localStorage`, avec une clé par classe et date. Le service worker met en cache la page et le manifeste. À la reconnexion, les opérations sont envoyées à l'API. `client_operation_id` et la contrainte unique empêchent les doublons.

## Notifications

La policy par défaut n'envoie rien pour `PRESENT`, `EXCUSED` ou un retard inférieur ou égal à 30 minutes. Une absence est évaluée uniquement après validation. Seuls les responsables actifs ayant `receives_sms=true` sont éligibles.

La queue suit `PENDING`, `PROCESSING`, `SENT`, `FAILED` ou `CANCELLED`. Le provider est injecté via `NotificationProvider`; `mock` est utilisé par défaut. Les erreurs sont retentées jusqu'à `ATTENDANCE_NOTIFICATION_MAX_ATTEMPTS`.

Configuration `.env`:

```dotenv
SMS_PROVIDER=mock
SMS_API_KEY=
SMS_SENDER=
ATTENDANCE_LATE_THRESHOLD_MINUTES=30
ATTENDANCE_NOTIFICATION_MAX_ATTEMPTS=3
ATTENDANCE_NOTIFICATION_RETRY_DELAY=5
```

## API principale

- `GET /api/teacher/classes`
- `GET /api/teacher/classes/{classRoom}/students`
- `POST /api/attendance/sessions`
- `POST /api/attendance/sessions/{attendanceSession}/sync`
- `POST /api/attendance/sessions/{attendanceSession}/validate`
- `GET /api/attendance/history` (Directeur)
- `GET /api/attendance/dashboard` (Directeur)

Toutes les routes sont authentifiées, protégées par CSRF pour les requêtes navigateur et limitées par le middleware de rate limiting.

## Tests

```bash
php artisan migrate:fresh --env=testing --force
php artisan test
```

Le test `AttendanceWorkflowTest` couvre l'affectation enseignant, l'inscription, la validation, l'anti-duplication, le provider mock et l'isolation inter-écoles.

## Hardening et validation

`TenantIsolationAttendanceTest` vérifie les accès directs d'un utilisateur d'une école aux sessions/classes d'une autre école, ainsi que les identifiants enseignant forgés. Le claim du worker utilise `lockForUpdate()` afin que deux workers ne traitent pas la même notification.

Le provider `http` utilise `Http::timeout()` et accepte les réponses 5xx, 429 et les erreurs réseau comme des échecs retentables. Les erreurs sont conservées dans `notification_queue.error`; après le nombre maximal d'essais, le statut devient `FAILED`.

Le dashboard Filament (`AttendanceOverview`) est visible uniquement par les directeurs et administrateurs. Ses filtres date/classe restent soumis au scope de l'école courante.

Les synchronisations verrouillent la session dans une transaction afin de sérialiser deux écritures concurrentes. Le worker verrouille également la ligne de queue avant son passage à `PROCESSING`; les contraintes uniques sur l'opération client et la clé d'idempotence métier empêchent les doublons.

Pour PostgreSQL, activer `pdo_pgsql`, créer une base vide et exécuter :

```bash
php artisan migrate:fresh --seed
php artisan test
```

Pour le test navigateur, ouvrir `/admin` avec un directeur puis `/teacher/attendance` avec un enseignant. Charger la classe, activer le mode hors ligne des outils réseau du navigateur, modifier un statut, recharger la page, puis réactiver le réseau et utiliser `Enregistrer`. Le statut `Synchronisé`, puis `Appel validé`, confirme l'ACK serveur.
