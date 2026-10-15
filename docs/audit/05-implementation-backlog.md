# Phase 5 — Implémentation des écarts par lots priorisés

1. Les priorités sécuritaires et d’isolation multi-école sont désormais renforcées et couvertes par les tests d’autorisation existants.
2. Le lot critique de sécurité couvre l’école, les rôles, les invitations, la désactivation, la validation de rôle et la navigation selon le contexte d’intervention.
3. Le lot académique/finance est stable : présence, validation, paiements, reversals, relances et solde sont contrôlés côté services et tests.
4. Le module notifications est déjà scindé entre rendu de template et canal de diffusion, ce qui permet un futur canal payant sans réécrire les workflows métier.
5. La vraie limite de la phase 5 n’est pas la logique métier, mais la finalisation du paramétrage réglementaire, du SMTP par école et de la validation navigateur réelle.
6. La priorité stratégique actuelle est de clôturer le périmètre “production utile” avant de lancer la finition UX et les documents imprimables.
7. Le modèle multi-tenant est bien aligné sur l’école comme frontière de données ; les ressources ne doivent plus être permises hors contexte.
8. Les processus de validation, d’approbation, de publication et de reverse restent les plus sensibles et doivent être couverts à chaque évolutions de logique.
9. Les tâches d’UX/mobile doivent rester un lot séparé de l’implémentation métier, car elles ne doivent pas introduire de changement d’architecture.
10. Le prochain bloc de travail utile est de finaliser le backlog de production et de préparer le rapport de clôture avec les risques résiduels et les décisions à valider.

## Source de preuve

- `app/Services/StaffInvitationService.php`
- `app/Services/RegistrationApprovalService.php`
- `app/Services/Notifications/TemplateRenderer.php`
- `app/Services/Notifications/WhatsappClickToChatChannel.php`
- `app/Services/Notifications/EmailChannel.php`
- `app/Http/Controllers/NotificationController.php`
- `tests/Feature/AttendanceWorkflowTest.php`
- `tests/Feature/FinanceWorkflowTest.php`
- `tests/Feature/RegistrationApprovalTest.php`
- `tests/Feature/StaffInvitationTest.php`
- `tests/Feature/NotificationScopeTest.php`

## Lot A — Critique : sécurité / isolation / finance

| ID | Correctif / sujet | État réel | Priorité | Preuve |
|---|---|---|---|---|
| P5-A-01 | Isolation multi-école stricte | Correctement appliquée via school_id + intervention temporaire | Critique | `StaffInvitationService`, `RegistrationApprovalService`, tests ACL |
| P5-A-02 | Invalidation de session utilisateur désactivé | Implémentée | Critique | `EnsureActiveUser.php` |
| P5-A-03 | Paiements immuables et reversal director-only | Implémentée | Critique | `FinanceWorkflowTest` |
| P5-A-04 | Numérotation / idempotence des paiements | Implémentée au niveau service | Critique | `FinanceWorkflowTest` |
| P5-A-05 | Accès de super admin hors contexte | Bloqué par intervention temporaire | Critique | tests d’intervention |

### Décision de mise en œuvre

Aucun changement architecturale majeur n’est requis pour ce lot. Les garde-fous existants sont cohérents avec le besoin métier et le cahier d’exigence.

## Lot B — ACL / rôles / données sensibles

| ID | Correctif / sujet | État réel | Priorité | Preuve |
|---|---|---|---|---|
| P5-B-01 | Rôles directeur / enseignant / comptable / censeur / secrétaire | Stable et testés | Très haute | `RoleNavigationVisibilityTest` |
| P5-B-02 | Accès direct aux ressources / pages | Vérifié par test 403/404 | Très haute | tests d’ACL |
| P5-B-03 | Restriction école active | Conformité active | Très haute | service d’intervention |
| P5-B-04 | Accès en lecture seule du censeur | Validé | Haute | `AttendanceWorkflowTest` |

### Décision de mise en œuvre

Le lot B est satisfaisant au niveau fonctionnel. Les axes à garder sous surveillance sont : modification de rôles, désactivation, onboarding and invitations de personnel, et toute nouvelle ressource Filament ajoutée.

## Lot C — Workflows métier bloquants

| ID | Workflow | État réel | Priorité | Remarque |
|---|---|---|---|---|
| P5-C-01 | W5 – Présence | Validé | Haute | idempotence + validation + notification |
| P5-C-02 | W7 – Facturation / encaissement | Validé | Haute | reçu / solde / reversal |
| P5-C-03 | W2 – Demande d’accès / création d’utilisateur | Validé | Haute | validation école + rôle + désactivation |
| P5-C-04 | W3 – Année scolaire / périodes | Partiellement validé | Moyenne | besoin de parcours final et verrouillage métier |
| P5-C-05 | W6 – Notes / bulletins | Partiel | Moyenne | publication + verrouillage à finaliser |
| P5-C-06 | W10 – Certificats / documents | Présent | Moyenne | validation de délivrance et registre |

### Décision de mise en œuvre

Aucune réécriture de la logique n’est nécessaire tant que le périmètre est conforme aux tests. Les écarts restants sont surtout de validation complète, pas de refonte.

## Lot D — Notifications / diffusion multi-canal

| ID | Correctif / sujet | État réel | Priorité | Preuve |
|---|---|---|---|---|
| P5-D-01 | TemplateRenderer : variables strictes | Implémenté | Haute | `TemplateRenderer.php` |
| P5-D-02 | WhatsApp click-to-chat | Implémenté sans envoi automatique | Haute | `WhatsappClickToChatChannel.php` |
| P5-D-03 | Email personnalisé par école | Prêt pour le contexte multi-école | Haute | `SchoolMailerService` / `EmailChannel` |
| P5-D-04 | Traçabilité honnête sans faux statut de livraison | Implémentée | Haute | `notification_logs` |
| P5-D-05 | Variables sans exposition dans les liens signés | Vérifié | Haute | `NotificationController` + tests |

### Décision de mise en œuvre

Le module notification est maintenant cohérent avec le cahier des charges : rendu des templates séparé du canal, lien WhatsApp manuel, email asynchrone, et journaux sans fausse livraison.

## Lot E — UX quotidienne / responsive / documents

| ID | Correctif / sujet | État réel | Priorité | Remarque |
|---|---|---|---|---|
| P5-E-01 | Navigation par rôle et worklists | Validé | Haute | dashboard + pages métiers |
| P5-E-02 | Responsive 320 / 390 / 768 / 1280 | Validé côté serveur | Haute | `ResponsiveAcademicPagesTest` |
| P5-E-03 | Langue française | Validé | Haute | `FrenchLocaleTest` |
| P5-E-04 | Documents PDF / A4 | à finaliser | Moyenne | validation navigateur / impression |
| P5-E-05 | États vides / confirmation UX | à finaliser | Moyenne | parcours de production |

### Décision de mise en œuvre

Cette partie ne doit pas déborder dans l’architecture ; elle doit rester un lot distinct après stabilisation métier.

## Blocages restants à valider

| Blocage | Nature | Impact | Décision |
|---|---|---|---|
| Paramétrage scolaire réglementaire | métier / validation | moyen | à valider par le client / direction |
| SMTP par école vs compte central | architecture | moyen | décision à confirmer avant production |
| Visibilité / textes PDF et attestations | produit / UI | moyen | finaliser avant mise en production |
| Couverture E2E navigateur complète W1–W12 | QA | important | compléter après stabilisation |

## Conclusion de phase

Le lot prioritaire est globalement maîtrisé : sécurité, ACL, workflow de base et notification sont dans un état exploitable. Les points restant sont surtout de décision produit, de validation réglementaire et de finalisation UX/documentation, sans refonte ni régression de fond.
