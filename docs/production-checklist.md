# Scolaris production checklist

## Infrastructure
- [ ] Render web service uses the repository `Dockerfile` and `/health`.
- [ ] Production domain, TLS, and alerting are configured.

## Database
- [ ] Managed PostgreSQL is provisioned and reachable.
- [ ] `php artisan migrate --force` succeeds on the target database.
- [ ] Foreign keys, unique constraints, and indexes are verified after migration.

## Environment
- [ ] `APP_KEY`, database credentials, and `APP_URL` are set in Render secrets.
- [ ] `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] `MAIL_*`, `SMS_*`, and payment provider variables are configured only when used.

## Security
- [ ] Super Admin, Director, Teacher, and Accountant accounts use unique passwords.
- [ ] Tenant isolation and IDOR checks pass for students, attendance, grades, report cards, templates, invoices, payments, receipts, and notifications.
- [ ] No `.env`, credentials, dumps, or private logs are deployed.

## Authentication / Multi-tenancy
- [ ] Login, inactive-user blocking, role permissions, and cross-school access are smoke-tested.
- [ ] A school user can only read and mutate records belonging to its school.

## Attendance / Notifications
- [ ] Teacher class assignment and attendance validation are tested.
- [ ] The database queue worker is running before enabling real notifications.
- [ ] SMS/WhatsApp providers remain explicitly marked not configured until real credentials are tested.

## Academic / Report cards
- [ ] Subjects, coefficients, assessments, grades, calculations, templates, HTML, and PDF are tested.
- [ ] PDF access is authenticated and tenant-scoped.

## Finance / Payments / Receipts
- [ ] Fees, invoices, manual payments, allocations, balances, debtors, reversals, and receipts are tested.
- [ ] Payment webhooks remain disabled until a signed provider integration is configured.

## Responsive / Design
- [ ] Login, dashboard, lists, attendance, grades, report cards, finance, payments, debtors, and reports are checked at 320, 375, 390, 768, and 1280 px.
- [ ] Scolaris palette, Roboto, readable focus states, empty states, and mobile actions are consistent.

## Logs / Backups
- [ ] Application logs are retained without passwords, tokens, or personal payloads.
- [ ] Render PostgreSQL backups and point-in-time recovery are enabled according to the selected plan.
- [ ] A restore drill and owner are documented.

## Deployment / Smoke tests
- [ ] `php artisan test`, `npm ci`, `npm run build`, and `docker build .` pass.
- [ ] `GET /`, `GET /login`, login, dashboard, student, attendance, grade, report-card PDF, fee, payment, and receipt workflows pass.
- [ ] Queue worker and scheduler processes are started only where required by enabled jobs.

## Legal / data governance
- [ ] Retention, deletion/archive, exports, and access responsibilities are approved by the school.
- [ ] GDPR/IPDCP and privacy notices receive separate legal/administrative validation.
