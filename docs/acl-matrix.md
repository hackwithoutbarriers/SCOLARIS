# Matrice ACL Scolaris

## Vue d’ensemble

| Rôle | Vue d’ensemble | Accès académique | Accès finance | Accès élèves | Accès admin |
|---|---|---|---|---|---|
| Directeur | Oui | Lecture + validation | Lecture + validation | Oui | Oui (école) |
| Enseignant | Classe affectée | Saisie et lecture sur matières affectées | Non | Lecture limitée | Non |
| Comptable | Oui | Non | Oui, finance école | Lecture limitée | Non |
| Censeur | Oui | Lecture globale école | Non | Lecture + présence | Non |
| Secrétaire | Oui | Lecture / gestion administrative | Non | Oui | Non |
| Super admin | Accès assisté, école active uniquement | Interventions auditées | Interventions auditées | Interventions auditées | Oui |

## Ressources clés

| Ressource | Directeur | Enseignant | Comptable | Censeur | Secrétaire | Super admin |
|---|---|---|---|---|---|---|
| Dashboard école | L | L | L | L | L | Interv. |
| Présence | LCMV | LCM affect. | — | L | LCM | Interv. |
| Notes / bulletins | LCMV | LCM affect. | — | L | — | Interv. |
| Paiements / reçus | LCMV | — | LCMV | — | — | Interv. |
| Dépenses / caisse | LCMV | — | LCMV | — | — | Interv. |
| Élèves / familles | LCM | — | — | — | LCM | Interv. |
| Invitations / comptes | LCM | — | — | — | LCM | Interv. |
| Paramètres école | LCM | — | — | — | — | Interv. |

L = lecture / liste, C = créer, M = modifier, V = validation / publication, — = aucun accès, Interv. = accès strictement dans une intervention active et journalisée.

## Règles de sécurité

- Aucun compte scolaire n’ouvre d’accès à une autre école sans intervention active.
- Les actions de suppression de paiement sont interdites ; seules les reversals sont autorisés.
- Les publications de bulletin sont verrouillées après publication.
- Les invitations et créations de comptes restent liées à l’école cible.
- Les routes, widgets et exports doivent répondre aux mêmes règles que les pages Filament.

Pour la preuve détaillée, voir [audit/02-matrice-acl.md](./audit/02-matrice-acl.md).
