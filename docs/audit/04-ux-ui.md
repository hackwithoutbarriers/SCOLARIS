# Phase 4 — UX / UI (état réel + correctifs)

1. Les pages Filament critiques et les parcours de rôle sont déjà testés pour le comportement principal sur mobile et bureau.
2. Le login et les pages académiques/financières sont disponibles à 320, 375, 390, 768 et 1280 px via des tests serveur ciblés.
3. Le tableau de bord sépare bien les indicateurs et les tâches à traiter, sans exposer de liens JSON techniques.
4. Les rôles ont des chemins d’action lisibles : directeur, comptable, enseignant, censeur, secrétaire.
5. La langue dominante est le français, et les tests ont déjà des assertions de libellés en français.
6. Les listes de travail (“worklists”) sont orientées métier et ne délèguent pas à des pages techniques.
7. Les flux de présence et de finance sont orientés opérationnels, avec des actions explicites sans écran technique inutile.
8. Les écarts restants sont surtout visuels et de parcours mobile : miminalisme, densité d’information, et validation E2E navigateur complète.
9. L’état actuel est exploitable pour la mise en œuvre quotidienne, mais pas encore totalement “production polish” au niveau design fin.
10. La prochaine étape logique est la validation navigateur fine, la révision des densités et les vérifications de cohérence visuelle sur des écrans réels.

## Source de preuve

Les éléments ci-dessous sont appuyés par les tests suivants :

- `tests/Feature/DashboardRoleActionsTest.php`
- `tests/Feature/ResponsiveAcademicPagesTest.php`
- `tests/Feature/FrenchLocaleTest.php`
- `app/Filament/Widgets/RoleActionCenter.php`
- `app/Filament/Pages/Auth/Login.php`

## 1) Parcours par rôle et efficacité de clics

| Rôle | Tâches quotidiennes prioritaires | Clics ciblés | État actuel |
|---|---|---:|---|
| Directeur | vérification des absences, suivi des impayés, validation notes, publication bulletin, gestion des comptes | ≤ 3 | Conformité générale; l’accès aux worklists est centralisé et les pages sont orientées action. |
| Enseignant | saisir présence, consulter notes de classe, valider absences, corriger notes, ouvrir suivi de matière | ≤ 3 | Correct; le rôle est limité aux classes/matières affectées et les pages sont visibles sans bruit financier. |
| Comptable | encaissement, suivi créances, génération reçu, dépense/solde, relance | ≤ 3 | Correct; le centre de recouvrement est séparé et les pages finance sont accessibles. |
| Censeur | présence, suivi d’absences, notes lecture, conformité académique | ≤ 3 | Correct; lecture académique et présence sur l’école, sans accès finance. |
| Secrétaire | dossiers élèves, présence, suivi des absences, contacts parents, accueil / inscription | ≤ 3 | Correct; le rôle ne voit pas les données de finance ni les notes en écriture. |

Observation :
- Les pages d’action sont bien branchées sur un tableau de bord par rôle.
- Les worklists ciblées remplacent la navigation “technique” sur les APIs et les vues de données brutes.
- Les pages de finance et académiques sont accessibles en quelques clics et non cachées dans des filtres opaques.

## 2) Tableaux de bord par rôle

| Zone | Exigence | État |
|---|---|---|
| Indicateurs | chiffres, KPI, synthèses | Oui, les widgets de rôle exposent des indicateurs clairs et ciblés. |
| Tâches à traiter | listes d’actions / worklists | Oui, les worklists sont séparées et ne mélangent pas les responsabilités. |
| État vide | message utile et action suivante | Partiel; les pages de worklists sont abordables, mais les cas vides doivent être plus explicitement validés par étude UX navigateur. |
| Navigation | pas de JSON/API dans les liens | Validé par test : `/api/...` n’apparaît pas dans le dashboard directeur. |
| Cohérence | chaque carte mène à une vraie page métier | Validé au niveau de la navigation par rôle et des pages worklists existantes. |

Preuves :
- `DashboardRoleActionsTest::test_director_worklists_are_available_and_dashboard_does_not_link_to_json_endpoints`
- `DashboardRoleActionsTest::test_censeur_and_secretary_dashboards_do_not_expose_financial_or_unassigned_academic_metrics`
- `app/Filament/Widgets/RoleActionCenter.php`

## 3) Responsive et tailles de touches

| Écran | Vérification | État |
|---|---|---|
| 320 px | login + pages académiques / finance | Validé par tests serveur avec `Viewport-Width` |
| 375 px | login / accès mobile | Validé |
| 390 px | mobile standard Android | Validé |
| 768 px | tablette | Validé |
| 1280 px | desktop | Validé |

Constat :
- le projet couvre bien la nécessité mobile-first actuelle avec des tests sur les largeurs de viewport cible ;
- l’absence d’un vrai runner navigateur limite la validation visuelle fine (taille des boutons, espacement, scroll, overflow) ;
- toutefois, la base de code et les pages Filament sont préparées pour un usage mobile.

Preuves :
- `ResponsiveAcademicPagesTest::test_filament_login_is_available_for_mobile_and_desktop_layouts`
- `ResponsiveAcademicPagesTest::test_grade_entry_and_import_pages_are_available_at_all_supported_viewports`
- `ResponsiveAcademicPagesTest::test_finance_pages_are_available_at_all_supported_viewports`

## 4) Cohérence visuelle

| Éléments | Cible | État |
|---|---|---|
| Palette | bleu royal / vert / doré | Bonne base de design déjà planifiée dans le produit ; à confirmer en production visuelle. |
| Police | Roboto | À vérifier sur le rendu réel dans le navigateur ; la base code ne semble pas contradictoire. |
| Couleurs sémantiques | vert = succès, or = attention, rouge = erreur | Conforme en concept ; plus de validation visuelle & WCAG requise. |
| Contraste | AA | À confirmer en rendu final, surtout sur les cartes de dashboard et les formulaires. |
| Icônes | cohérence métier | Correcte dans l’architecture Filament actuelle ; vérification visuelle recommandée pour uniformité. |

Conclusions :
- la cohérence visuelle est globalement alignée sur le design DMA souhaité ;
- il manque encore une validation navigateur de l’UI réelle pour prouver le contraste, l’espacement et les états d’erreur.

## 5) Langue et ton

| Zone | Exigence | État |
|---|---|---|
| Libellés | français uniquement | Validé par tests d’assertions de libellés français. |
| Messages d’erreur | explicites et orientées action | Partiellement validé; la base est correcte, mais chaque cas métier doit être auditée en parcours complet. |
| Notifications | français | À confirmer dans les templates de notification et des messages de workflow. |
| PDF / attestations | français | À confirmer dans les moteurs de document et les textes réglementaires. |

Preuves :
- `tests/Feature/FrenchLocaleTest.php`
- `app/Filament/Pages/Auth/Login.php`

Observation :
- le projet s’efforce fortement vers le français, ce qui est bon pour le public cible et le besoin mobile.
- les textes réglementaires et les modèles de message doivent être validés avant mise en prod pour éviter la sur-législation ou les formulations non conformes.

## 6) Retour d’état honnête

| Point | Exigence | État |
|---|---|---|
| Local / synchro / validé | états clairs dans l’UI | Partiel, mais le principe est présent côté workflow métier. |
| Confirmation avant actions irréversibles | publication, clôture, reversal | Validé au niveau métier et logiquement aligné sur les policies. |
| États de saisie | idempotence, double-clic, rechargement | Partiellement couvert par les services métier et les tests de workflows. |

Observation :
- le produit est bien structuré pour ne pas produire d’actions dangereuses sans confirmation métier ;
- le besoin UX “offline / enregistré localement / synchronisé” est un point d’amélioration à la prochaine itération, surtout pour la saisie mobile sur réseau instable.

## 7) Performance perçue et charge de page

| Sujet | Exigence | État |
|---|---|---|
| Dashboard | pas de requête bloquante lourde | Correcte sur les pages de rôle et les widgets ciblés. |
| Pagination | sur grandes listes | Présent dans les mécanismes Filament, à valider sur chaque ressource. |
| Chargement différé | widgets lourds | À confirmer sur les widgets de synthèse et tableaux de bord. |
| Intégration mobile | navigation légère | Conforme aux principes de l’architecture actuelle. |

Constat :
- le système n’a pas de blocage majeur connu sur le chargement, mais il manque une mesure de performance réelle sur les pages de grande taille.

## 8) Documents imprimables

| Type | Exigence | État |
|---|---|---|
| Bulletin | A4, en-tête école, rendu écran/impression cohérent | Présent au niveau des modèles de rapport, mais besoin de validation navigateur et PDF final. |
| Reçu | numérotation et mise en page | Validé au niveau logique finance, à finaliser côté impression / PDF. |
| Attestation | registre et délivrance | Partie architecture présente, mais la validation produit reste à compléter. |
| Carte scolaire | QR opaque | Architecture prévue, testabilité à confirmer. |

Conclusion :
- le projet a les briques de document, mais la phase UX/UI doit vérifier le rendu imprimable réel, surtout sur les dispositions A4 et les PDF générés.

## 9) Écarts constatés et correctifs requis

| ID | Sévérité | Écart / preuve | Correctif proposé | Effort |
|---|---|---|---|---|
| P4-UX-01 | Majeur | Validation UI uniquement serveur; pas de test navigateur allant jusqu’aux écrans sur mobile. | Ajouter un parcours Playwright / Dusk ciblé sur login, dashboard, présence, finance, bulletins. | M |
| P4-UX-02 | Moyen | Absence de validation réelle de contraste / WCAG AA sur les composants visuels clés. | Auditer les cartes de dashboard, formulaires, boutons d’action et messages d’erreur. | M |
| P4-UX-03 | Moyen | Les états vides n’ont pas été confirmés sur tous les workflows critiques. | Prévoir messages “Aucun impayé aujourd’hui”, “Aucune absence à traiter”, etc. | S |
| P4-UX-04 | Mineur | Les documents imprimables restent à valider au niveau rendu PDF final. | Contrôler les templates A4 et la cohérence écran/impression. | M |
| P4-UX-05 | Mineur | Le retour d’état “offline / synchronisé” n’est pas produit au niveau UX explicit. | Introduire un indicateur de synchronisation pour les saisies hors ligne. | M |

## 10) Correctifs déjà appliqués / validés

| Correctif | Preuve | Résultat |
|---|---|---|
| Dashboard orienté métier, sans liens API techniques | `DashboardRoleActionsTest` | Validé |
| Séparation rôle × dashboard / worklist | `RoleNavigationVisibilityTest` + `DashboardRoleActionsTest` | Validé |
| Login et pages critiques sur 320–1280 px | `ResponsiveAcademicPagesTest` | Validé |
| Francisation du lexique visible | `FrenchLocaleTest` | Validé |
| Travail de rôle orienté action et non technique | `DashboardRoleActionsTest` | Validé |

## Conclusion de phase

La phase 4 confirme que l’application est globalement viable en usage professionnel mobile et bureau, avec des parcours de rôle lisibles et une navigation métier fonctionnelle. Les points restants ne sont pas bloquants pour le lancement fonctionnel, mais ils doivent être confirmés sur rendu réel navigateur pour le polish final, le contraste visuel, les états vides et les documents imprimables.
