# Cartographie fonctionnelle SCOLARIS

> Rapport d'audit exploitable pour prioriser les prochaines évolutions produit, UX et métier.
>
> **Périmètre audité :** modèles de rôles, policies, panneau Filament, dashboard/widgets, ressources CRUD, pages métier, routes web/API, présence, scolarité, bulletins, finance, onboarding et demandes d'accès.
>
> **Date de l'audit :** 2026-09-09
>
> **Méthode :** lecture du code présent dans `app/`, `routes/`, `resources/views/` et `tests/`. Les droits décrits ici correspondent au comportement implémenté, pas à une intention supposée.

## 1. Synthèse décisionnelle

### Ce que l'application couvre déjà

- Gestion multi-école avec `school_id`, scopes tenant et contrôles dans les policies/services.
- Quatre personas métier opérationnels : directeur, enseignant, comptable, super administrateur.
- Dashboard orienté indicateurs et actions : absences, notes, bulletins, impayés, présence et statistiques école.
- Paramétrage scolaire : années, périodes, classes, matières, configuration des matières, affectations enseignants.
- Gestion des personnes : élèves, responsables, utilisateurs.
- Parcours notes : saisie rapide, import CSV avec prévisualisation, notes et évaluations.
- Parcours bulletins : modèles, génération HTML/PDF et statuts.
- Parcours présence : prise mobile, reprise locale, synchronisation, validation.
- Parcours finance : grilles tarifaires, paiements, reçus, débiteurs, rapports et webhook.
- Onboarding directeur en cinq étapes.
- Demandes d'accès avec approbation/rejet.

### Décisions prioritaires recommandées

| Priorité | Décision | Pourquoi |
|---|---|---|
| P0 | Remplacer les liens dashboard qui renvoient directement vers des endpoints JSON par des pages métier lisibles | **Implémenté :** pages `Absences à traiter` et `Centre de recouvrement`. |
| P0 | Finaliser une matrice de droits explicite par ressource et par action | **Partiellement implémenté :** pages P0 protégées par rôle et couvertes par tests ; matrice complète des ressources à poursuivre. |
| P0 | Ajouter une vraie séparation « indicateur » / « tâche à traiter » | **Implémenté sur les priorités :** pages `Notes à compléter`, `Absences à traiter` et `Centre de recouvrement`. |
| P1 | Uniformiser tous les libellés de navigation en français | **Implémenté pour les pages notes :** `GradeEntry` et `GradeImport` utilisent désormais `Scolarité`. |
| P1 | Donner un état vide métier à chaque module | Les prochaines actions doivent être visibles quand l'école démarre sans données. |
| P1 | Ajouter des tests de visibilité dashboard par rôle | **Partiellement implémenté :** tests d'accès des pages d'action pour directeur, enseignant et comptable ; rendu détaillé des widgets à compléter. |
| P2 | Ajouter filtres de période/année actifs dans tous les indicateurs | Plusieurs widgets agrègent sans filtre explicite de période dans leur propre requête. |

## 2. Modèle de rôles et portée

## Décisions métier validées et règles applicatives

Les décisions suivantes sont désormais la référence fonctionnelle :

- **Finance :** le comptable peut consulter, enregistrer et corriger une opération dans son école. La suppression reste réservée au directeur ; une correction doit utiliser le mécanisme transactionnel de reversal et exiger un motif, plutôt qu'une suppression directe.
- **Bulletins :** l'enseignant ne voit que les bulletins des élèves de ses classes, mais peut consulter le bulletin complet de ces élèves. Il soumet le bulletin pour vérification ; le directeur l'approuve puis le publie.
- **Super administrateur :** son tableau de bord est multi-écoles et agrégé. Les écrans d'assistance doivent sélectionner explicitement l'école cible et journaliser les interventions sensibles.
- **KPI :** les indicateurs doivent progressivement accepter les contextes « aujourd'hui », « période active », « année scolaire active » et dates personnalisées.
- **Facturation :** une inscription active déclenche une facture si une grille active correspondant à l'année et à la classe existe et contient des frais actifs. La génération est idempotente ; aucune facture n'est créée pour une configuration vide.
- **Clôture :** une période togolaise reste ouverte jusqu'à la vérification des bulletins. Le directeur peut la clôturer lorsque aucun bulletin n'est encore en brouillon, en revue ou en révision. Une période clôturée ne peut plus faire évoluer les bulletins.

Ces règles sont implémentées dans `PaymentPolicy`, `ReportCardResource`, `ReportCardService`, `InvoiceService`, `Enrollment`, `TermClosureService` et la migration d'état des périodes. Les règles de calendrier (trimestre, semestre, compositions et examen final) restent paramétrables et ne sont pas figées par le code.

## Audit actualisé — 9 septembre 2026

### Évolutions implémentées dans cette itération

- **Clôture depuis Filament :** l'action « Clôturer la période » est disponible dans la ressource des périodes, réservée au directeur, confirmée explicitement et idempotente.
- **Suivi des bulletins :** la page « Suivi des bulletins » présente les volumes par état et la liste filtrable par classe et période.
- **Verrouillage métier :** une période clôturée bloque les changements de notes, bulletins, présences, inscriptions et factures dont la date appartient à la période. Une correction future devra passer par une réouverture contrôlée et journalisée.
- **Interventions SaaS :** les ouvertures d'assistance et accès administratifs ciblés du super administrateur sont journalisés sous des actions `super_admin.*`, avec école, utilisateur, date, adresse IP et contexte.
- **KPI :** le dashboard propose aujourd'hui, période active, année scolaire active et période personnalisée. Les widgets académiques et financiers consomment le périmètre résolu.
- **Abonnements :** les plans, abonnements par école, périodes d'essai, statuts, échéances et notes sont modélisés. Le super administrateur dispose d'une ressource Filament dédiée et voit le plan dans la supervision multi-écoles.

### Matrice actuelle des surfaces

| Surface | Directeur | Enseignant | Comptable | Super administrateur |
|---|---|---|---|---|
| Dashboard école | KPIs et actions de son école | actions de ses classes | recouvrement et paiements | dashboard SaaS agrégé |
| Bulletins | approuve et publie | complète et soumet ses classes | aucun accès métier par défaut | supervision globale contrôlée |
| Présences | supervise et corrige | saisit ses classes | aucun accès métier | assistance ciblée |
| Finance | administre et supprime avec prudence | aucun accès | enregistre et corrige par reversal | supervision globale |
| Périodes | crée et clôture | consulte | consulte | administration plateforme |
| Abonnements | aucun accès SaaS | aucun accès SaaS | aucun accès SaaS | crée, modifie et suit les plans/abonnements |

### Risques et suites recommandées

1. Ajouter une procédure de réouverture avec motif, approbation et expiration automatique.
2. Séparer techniquement les verrous académique, présence, inscription et finance si le métier veut rouvrir un seul domaine.
3. Ajouter une table de facturation SaaS (factures d'abonnement, paiements, avoirs) distincte des factures élèves.
4. Brancher les transitions d'abonnement à un fournisseur de paiement et à des notifications, sans mélanger les données de l'école et celles du SaaS.
5. Ajouter des tests d'acceptation Filament pour l'action de clôture, le suivi filtré et le journal d'intervention.

### Références de conception consultées

- Moodle, verrouillage des notes : https://docs.moodle.org/4x/en/Grade_locking
- PowerSchool, préparation et finalisation des bulletins : https://ps.powerschool-docs.com/powerteacher-pro/latest/getting-ready-for-report-cards
- PowerSchool, années et périodes : https://ps.powerschool-docs.com/pssis-admin/latest/years-and-terms
- Fedena, verrouillage des présences : https://support.fedena.com/support/solutions/articles/245379-how-to-lock-the-attendance-after-a-duration
- OpenEduCat, workflow d'inscription : https://doc.openeducat.org/applications/enrollment/enrollment-workflow.html
- Fedena, configuration financière : https://support.fedena.com/support/solutions/articles/245404-fee-module-configuration

Ces références convergent vers une règle importante : la date de fin ne doit pas clôturer silencieusement une période. Il faut une transition explicite, des vérifications, un verrou côté serveur, un audit et une procédure de correction privilégiée.

| Rôle technique | Libellé métier | École obligatoire | Accès panneau | Portée |
|---|---|---:|---:|---|
| `super_admin` | Super administrateur plateforme | Non | Oui si actif, e-mail vérifié | Administration transversale ; `before()` des policies retourne `true`. |
| `director` | Directeur | Oui | Oui si actif, e-mail vérifié | Administration et pilotage de sa propre école. |
| `admin` / `principal` | Alias directeur | Oui | Oui si actif, e-mail vérifié | Même comportement que directeur via `isDirector()`. |
| `teacher` | Enseignant | Oui | Oui si actif, e-mail vérifié | Classes/élèves affectés, présence et notes autorisées. |
| `accountant` | Comptable | Oui | Oui si actif, e-mail vérifié | Finance de sa propre école ; pas de gestion académique générale. |

### Règles transverses observées

- Un compte inactif ne doit pas accéder au panneau ni aux API protégées.
- Le super administrateur contourne les policies de ressource via `before()`, mais doit rester soumis aux contrôles d'action administrative sensibles.
- Les ressources tenant sont filtrées par école via les scopes/relations et vérifications de policy.
- Les actions UI ne constituent pas une protection suffisante : les contrôleurs/services/policies doivent rester la source d'autorité.

## 3. Dashboard : cartographie des cartes et widgets

Le dashboard `App\Filament\Pages\Dashboard` monte toujours :

1. `RoleActionCenter`
2. `SchoolStatsOverview`
3. `AcademicOverview`

Puis ajoute :

- `AttendanceOverview` pour `isAdmin()` : super administrateur ou directeur actif.
- `FinanceOverview` pour `isFinanceOperator()` : super administrateur, directeur ou comptable actif.

### 3.1 Directeur

#### Cartes visibles

| Carte/widget | Donnée affichée | Action actuelle | Décision métier |
|---|---|---|---|
| Absences à traiter | Nombre d'absences du jour | Lien vers `/api/attendance/history` | Identifier les absences à justifier et piloter les relances. |
| Notes manquantes | Grades avec score nul | Lien vers saisie des notes | Compléter les évaluations incomplètes. |
| Bulletins à publier | Bulletins `review` ou `approved` | Ressource Bulletins | Vérifier puis publier les bulletins. |
| Impayés | Solde des factures ouvertes | Ressource Paiements | Prioriser le recouvrement. |
| Élèves | Total élèves | Aucune action explicite dans le widget | Surveiller le périmètre de l'école. |
| Responsables | Total responsables | Aucune action explicite | Vérifier la qualité des contacts. |
| Classes | Total classes | Aucune action explicite | Contrôler la structure scolaire. |
| Années scolaires en cours | Nombre d'années courantes | Aucune action explicite | Vérifier le paramétrage actif. |
| Enseignants | Total enseignants | Aucune action explicite | Contrôler les effectifs pédagogiques. |
| Indicateurs académiques | élèves évalués, moyenne, matières sans notes, états bulletins | Pas toujours de lien | Décider des priorités pédagogiques et éditoriales. |
| Présence | taux, absents, retards, appels non validés | Filtre date/classe dans le widget | Suivre la ponctualité et la complétude des appels. |
| Finance | attendu, encaissé, impayés, taux de recouvrement | Indicateur | Piloter la trésorerie scolaire. |

#### Ce que le directeur peut faire

- Configurer l'école et reprendre l'onboarding.
- Gérer élèves, responsables, classes, années, périodes, matières et affectations.
- Gérer utilisateurs de son école et demandes d'accès qui lui sont destinées.
- Créer/modifier les évaluations et notes de son école.
- Importer des notes CSV après prévisualisation.
- Vérifier/gérer les bulletins et leurs modèles.
- Consulter, créer, modifier et supprimer les paiements selon la policy.
- Consulter les reçus et rapports finance.
- Consulter les présences et les indicateurs de pilotage.

#### Risques/écarts à traiter

- Une carte « Absences à traiter » ouvre un endpoint JSON au lieu d'une vue de traitement.
- Plusieurs statistiques n'ont pas de CTA direct.
- Le dashboard ne présente pas explicitement les tâches du jour, les anomalies ou les échéances.
- Les cartes académiques méritent des filtres de période cohérents avec le formulaire du dashboard.

### 3.2 Enseignant

#### Cartes visibles

| Carte/widget | Donnée affichée | Action actuelle | Décision métier |
|---|---|---|---|
| Classes du jour | Nombre de classes/affectations distinctes | Prise de présence mobile | Ouvrir la classe et prendre l'appel. |
| Présences à terminer | Appels du jour non validés | Prise de présence mobile | Reprendre et terminer l'appel. |
| Évaluations sans notes | Évaluations sans grade | Ressource Notes | Compléter les notes. |
| Statistiques école | Élèves, responsables, classes, année, enseignants | Pas de CTA direct | Contexte, mais pas une tâche enseignant. |
| Indicateurs académiques | Agrégats académiques visibles par défaut | Pas de CTA systématique | Suivre son activité, à restreindre/adapter si nécessaire. |

#### Ce que l'enseignant peut faire

- Ouvrir la page mobile de présence.
- Lister ses classes et les élèves de ses classes.
- Créer/synchroniser/valider les appels autorisés.
- Consulter l'historique de présence autorisé.
- Voir les notes des élèves de ses classes.
- Gérer les notes d'une évaluation dont il est le responsable, uniquement pour les élèves autorisés.
- Consulter les bulletins des élèves auxquels il est rattaché, selon `viewReportCard`.

#### Ce que l'enseignant ne doit pas faire

- Gérer les élèves, utilisateurs, classes, années ou affectations globales.
- Modifier les paiements ou les grilles tarifaires.
- Modifier les bulletins ou les paramètres d'école.
- Lire les élèves ou évaluations d'une autre école.

#### Risques/écarts à traiter

- `SchoolStatsOverview` et `AcademicOverview` ne déclarent pas de `canView()` : leur pertinence et leur niveau de détail doivent être confirmés pour un enseignant.
- Le nombre « Classes du jour » repose actuellement sur les affectations distinctes, pas explicitement sur les cours du jour.
- L'action « Évaluations sans notes » ouvre une ressource générale ; une vue filtrée par enseignant serait plus efficace.

### 3.3 Comptable

#### Cartes visibles

| Carte/widget | Donnée affichée | Action actuelle | Décision métier |
|---|---|---|---|
| Encaissé aujourd'hui | Paiements confirmés du jour | Paiements | Contrôler les encaissements. |
| Reste à recouvrer | Solde des factures ouvertes | `/api/payments/debtors` | Traiter les débiteurs. |
| Impayés vieillissants | Nombre de factures en retard | `/api/payments/report?type=debtors` | Prioriser les relances. |
| FinanceOverview | attendu, encaissé, impayés, taux | Indicateurs | Suivre la performance de recouvrement. |
| Statistiques générales/académiques | Widgets communs | Pas de CTA | À réduire ou contextualiser pour éviter le bruit. |

#### Ce que le comptable peut faire

- Consulter les paiements de son école.
- Créer/enregistrer un paiement.
- Consulter les reçus.
- Consulter les débiteurs et rapports finance.
- Utiliser les indicateurs de recouvrement.

#### Ce que le comptable ne doit pas faire

- Modifier ou supprimer un paiement : `PaymentPolicy::update/delete()` réserve cette action au directeur.
- Gérer les élèves, notes, classes, bulletins ou utilisateurs.
- Accéder aux données financières d'une autre école.

#### Risques/écarts à traiter

- Les deux cartes débiteurs renvoient vers des URLs API ; il faut une page finance dédiée avec filtres, ancienneté, relance et historique.
- Le comptable voit des widgets académiques généraux sans possibilité d'action correspondante.
- `FinanceOverview` calcule des agrégats globaux dans le widget ; confirmer que les scopes tenant et les filtres de période sont systématiquement actifs.

### 3.4 Super administrateur

#### Cartes visibles

- Même `RoleActionCenter` que le directeur, car il ne correspond ni au rôle enseignant ni au rôle comptable.
- Statistiques école, académiques, présence et finance.
- Accès aux ressources d'administration plateforme et aux données des écoles selon le bypass policy.

#### Ce qu'il peut faire

- Administrer les écoles.
- Administrer les utilisateurs et demandes d'accès.
- Superviser les données métier et la finance.
- Accéder au panneau si actif et vérifié.

#### Décision à prendre

Le super administrateur devrait probablement disposer d'un dashboard distinct, agrégé par école, plutôt que de cartes présentant des volumes comme s'il pilotait une seule école.

## 4. Navigation et modules fonctionnels

### Administration

| Module | Fonction | Directeur | Enseignant | Comptable | Super admin |
|---|---|---:|---:|---:|---:|
| Écoles | Créer/modifier et activer les écoles | Limité selon policy | Non | Non | Oui |
| Utilisateurs | Gérer comptes/rôles de l'école | Oui | Non | Non | Oui |
| Demandes d'accès | Approuver/rejeter | Oui pour sa portée | Non | Non | Oui |
| Démarrage de l'école | Onboarding cinq étapes | Oui | Non | Non | À décider |

### Personnes

| Module | Fonction | Directeur | Enseignant | Comptable | Super admin |
|---|---|---:|---:|---:|---:|
| Élèves | Dossier élève, import, modification | Oui | Lecture métier limitée | Non | Oui |
| Responsables | Contacts et rattachements | Oui | Lecture indirecte selon besoin | Non | Oui |

### Paramétrage scolaire

| Module | Fonction | Directeur | Enseignant | Comptable | Super admin |
|---|---|---:|---:|---:|---:|
| Années scolaires | Périodes d'activité | Oui | Non | Non | Oui |
| Périodes | Trimestres/semestres | Oui | Lecture indirecte | Non | Oui |
| Classes | Structure des classes | Oui | Lecture de ses affectations | Non | Oui |
| Matières | Référentiel matières | Oui | Lecture | Non | Oui |
| Configuration matières | méthode, seuil, coefficient | Oui | Lecture | Non | Oui |
| Affectations enseignants | classe/matière/année | Oui | Lecture de ses affectations | Non | Oui |
| Inscriptions | élève/classe/année/statut | Oui | Lecture de ses classes | Non | Oui |

### Scolarité

| Module | Fonction | Directeur | Enseignant | Comptable | Super admin |
|---|---|---:|---:|---:|---:|
| Évaluations | créer/modifier les évaluations | Oui | Selon attribution/policy | Non | Oui |
| Notes | saisie, correction, import | Oui | Ses évaluations et élèves autorisés | Non | Oui |
| Saisie rapide | workflow notes | Oui | Oui | Non | Oui |
| Import CSV notes | prévisualiser puis importer | Oui | Selon `canAccess` actuel à confirmer | Non | Oui |
| Bulletins | consulter, vérifier, publier selon actions | Oui | Consultation de ses élèves | Non | Oui |
| Modèles de bulletins | construire les modèles | Oui | Non | Non | Oui |

### Finance

| Module | Fonction | Directeur | Enseignant | Comptable | Super admin |
|---|---|---:|---:|---:|---:|
| Grilles tarifaires | définir les frais | Oui | Non | Non | Oui |
| Paiements | consulter/enregistrer | Oui | Non | Oui | Oui |
| Paiement | modifier/supprimer | Oui | Non | Non | Oui |
| Reçus | générer/consulter | Oui | Non | Oui | Oui |
| Débiteurs | analyser et relancer | Oui | Non | Oui | Oui |
| Webhook paiement | intégration fournisseur | Système signé | Système signé | Système signé | Système signé |

### Présence

| Fonction | Directeur | Enseignant | Comptable | Super admin |
|---|---:|---:|---:|---:|
| Voir tableau de présence | Oui | Selon autorisation et classes | Non | Oui |
| Prendre l'appel | Supervision | Oui, classes affectées | Non | Supervision |
| Enregistrer/synchroniser | Oui selon endpoint/policy | Oui | Non | Oui |
| Valider définitivement | Oui selon rôle/endpoint | Oui selon classe/session | Non | Oui |
| Voir historique | Oui | Ses données autorisées | Non | Oui |

## 5. Parcours métier de bout en bout

### A. Mise en service d'une école

1. Demande d'accès publique.
2. Validation super admin ou directeur selon le rôle demandé.
3. Connexion et accès au panneau.
4. Onboarding : école → année/périodes → classes → utilisateurs → import élèves.
5. Paramétrage matières et affectations.
6. Création des évaluations et grilles tarifaires.

**Point de contrôle recommandé :** afficher un checklist de complétude et bloquer uniquement les actions qui nécessitent réellement une donnée absente.

### B. Présence

1. Enseignant choisit une classe.
2. Les élèves sont chargés avec portée tenant/affectation.
3. L'enseignant marque présent, en retard, absent ou excusé.
4. Les modifications sont persistées localement si nécessaire.
5. Synchronisation API.
6. Validation définitive.
7. Historique et traitement des absences.

**Point de contrôle recommandé :** distinguer visuellement « enregistré localement », « synchronisé » et « validé définitivement ».

### C. Notes

1. Directeur/enseignant choisit l'évaluation autorisée.
2. Saisie rapide ou import CSV.
3. Prévisualisation sans écriture.
4. Contrôle colonnes, encodage, relations, score maximum et tenant.
5. Confirmation.
6. Écriture transactionnelle.
7. Rapport de lignes créées, modifiées, rejetées.

**Point de contrôle recommandé :** fournir une liste de tâches filtrée par enseignant, classe et période au lieu d'une ressource générale.

### D. Bulletins

1. Notes et présence constituent les données sources.
2. Bulletin en brouillon.
3. Aperçu HTML/PDF.
4. Vérification des notes manquantes/anomalies.
5. Approbation/publication.
6. Historique/version et téléchargement.

**Point de contrôle recommandé :** exposer sur le dashboard le nombre d'anomalies bloquantes avant publication.

### E. Recouvrement

1. Grille tarifaire.
2. Facture/solde élève.
3. Enregistrement du paiement.
4. Confirmation/idempotence.
5. Reçu.
6. Débiteur et ancienneté.
7. Relance et historique.

**Point de contrôle recommandé :** remplacer les liens API des cartes par une vue « Centre de recouvrement ».

## 6. Actions, tâches et champs nécessaires

Cette section propose le format de fiche à utiliser pour chaque future fonctionnalité.

| Champ | Valeur à renseigner |
|---|---|
| Nom de la tâche | Verbe métier + objet : « Vérifier les bulletins de la 3e A » |
| Persona responsable | Directeur, enseignant, comptable ou super admin |
| École/périmètre | École, classe, période, élève ou global plateforme |
| Déclencheur | KPI, échéance, anomalie, import, demande utilisateur |
| Précondition | Données et permissions nécessaires |
| Données affichées | Colonnes, indicateurs, statut, date, responsable |
| Action principale | Bouton unique prioritaire |
| Actions secondaires | Voir, filtrer, exporter, corriger, annuler |
| Règle métier | Condition d'autorisation ou de validation |
| Statut | À faire, en cours, bloqué, soumis, validé, publié, clôturé |
| Événement d'audit | Action, utilisateur, école, ancien/nouveau statut |
| Échec attendu | 401, 403, 404, 409, 422, 429 ou erreur réseau |
| Responsive | Comportement à 320/390/768/1280 px |
| Critère d'acceptation | Résultat observable et testable |

### Backlog initial de tâches produit

| ID | Tâche | Persona | Priorité | Statut actuel | Critère d'acceptation |
|---|---|---|---|---|---|
| DASH-01 | Créer la vue « Absences à traiter » | Directeur | P0 | Carte vers API | La carte ouvre une liste filtrée, avec justification et statut. |
| DASH-02 | Créer le centre de recouvrement | Directeur, comptable | P0 | Cartes + endpoints | Débiteurs, ancienneté, relance, paiement et reçu dans une même vue. |
| DASH-03 | Filtrer les notes manquantes par enseignant/classe/période | Directeur, enseignant | P0 | Compteur global | Chaque ligne possède un CTA vers la saisie autorisée. |
| DASH-04 | Ajouter une matrice de droits affichable | Équipe produit | P0 | Policies dispersées | Chaque ressource a view/create/update/delete/export documentés et testés. |
| DASH-05 | Dashboard super admin multi-écoles | Super admin | P1 | Dashboard école-like | Sélection d'école et agrégats séparés par tenant. |
| DASH-06 | État vide métier de chaque module | Tous | P1 | Partiel | Chaque état vide explique l'enjeu et propose une action autorisée. |
| DASH-07 | Uniformiser les groupes et libellés français | Tous | P1 | `Academic` résiduel | Aucun libellé utilisateur anglais visible. |
| DASH-08 | Ajouter filtres période/année aux KPI | Directeur, super admin | P1 | Filtres partiels | Les cartes indiquent leur période et recalculent de manière cohérente. |
| DASH-09 | Ajouter tests widgets par rôle | QA | P1 | Tests HTTP/responsive | Les widgets visibles et invisibles sont vérifiés pour chaque rôle. |
| DASH-10 | Ajouter journal d'audit métier exploitable | Direction, conformité | P1 | Partiel | Notes, présence, paiements, bulletins, rôles et actions sensibles sont traçables. |
| DASH-11 | Vue enseignant « Ma journée » | Enseignant | P2 | Cartes génériques | Classes, appels, évaluations et tâches sont limités à ses affectations. |
| DASH-12 | Vue directeur « Pilotage » | Directeur | P2 | KPI génériques | Les indicateurs sont ordonnés par urgence et conduisent à une action. |

## 7. Matrice des tests à maintenir

Pour chaque rôle et chaque école, prévoir :

- accès autorisé à sa ressource ;
- refus `403` d'une action interdite ;
- refus d'une ressource d'une autre école ;
- impossibilité de modifier une ressource d'une autre école ;
- impossibilité de supprimer une ressource d'une autre école ;
- export limité au tenant ;
- API et Filament produisant la même décision ;
- compte inactif rejeté ;
- absence de données affichant un état vide exploitable ;
- responsive vérifié à 320, 390, 768 et 1280 px.

### Cas prioritaires

| Cas | Directeur | Enseignant | Comptable | Super admin |
|---|---:|---:|---:|---:|
| Voir élève de son école | Oui | Selon classe | Non | Oui |
| Voir élève d'une autre école | Non | Non | Non | Oui selon supervision |
| Modifier une note de son périmètre | Oui | Seulement évaluation/élève autorisés | Non | Oui |
| Modifier un paiement | Oui | Non | Non | Oui |
| Prendre présence | Supervision | Oui selon affectation | Non | Supervision |
| Publier bulletin | Oui selon workflow | Non | Non | Oui |
| Gérer utilisateur | Oui dans école | Non | Non | Oui |
| Export finance | Oui | Non | Oui | Oui |

## 8. Points à clarifier avec le métier

1. Le comptable peut-il uniquement enregistrer un paiement, ou doit-il aussi annuler/corriger avec validation du directeur ?
2. Un enseignant peut-il voir les bulletins complets ou seulement les élèves de ses classes et les matières qu'il enseigne ?
3. Le directeur doit-il pouvoir publier directement, ou une approbation à deux niveaux est-elle obligatoire ?
4. Le super admin doit-il voir des données nominatives ou uniquement des agrégats par école ?
5. Les KPI du dashboard doivent-ils être « aujourd'hui », « période active », « année scolaire active » ou configurables ?
6. Une facture est-elle créée automatiquement à l'inscription, ou manuellement par la finance ?
7. Quelle action constitue la clôture officielle d'une période scolaire ?

## 9. Conclusion

SCOLARIS possède déjà une base métier solide et une séparation des rôles cohérente. La prochaine étape ne devrait pas être d'ajouter davantage de CRUD, mais de transformer les indicateurs actuels en files de travail décisionnelles :

- un directeur doit voir ce qui bloque l'établissement et agir immédiatement ;
- un enseignant doit voir sa journée et ses classes, sans bruit administratif ;
- un comptable doit voir les débiteurs et les actions de recouvrement ;
- un super administrateur doit piloter les écoles et non une école fictive.

La priorité est donc de construire les trois centres d'action `Pilotage`, `Ma journée` et `Recouvrement`, puis de verrouiller leur matrice de droits et leurs tests tenant.
