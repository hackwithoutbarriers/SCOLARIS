# 00 — Diagnostic de l’existant

## Résumé en 10 lignes

1. [VÉRIFIÉ] Scolaris possède déjà les années scolaires, classes, matières, élèves, inscriptions, responsables et rôles de base.
2. [VÉRIFIÉ] L’import élèves est une commande CSV ; le chemin d’exemple ne transforme pas sa colonne « classe » en inscription.
3. [VÉRIFIÉ] Paiements, reçus, dépenses avec validation, journal de caisse et clôture existent ; les paiements ne sont pas exclusivement espèces dans le modèle.
4. [VÉRIFIÉ] Appel, synchronisation, validation, historique et tableau de suivi de présence existent ; l’appel en moins de 30 secondes n’est pas mesuré.
5. [VÉRIFIÉ] Notes, import CSV, workflow de bulletin versionné et PDF existent ; conseil de classe assisté et décisions de passage ne sont pas établis par les preuves examinées.
6. [VÉRIFIÉ] Les relances planifiées, l’email et WhatsApp Click-to-Chat existent ; « lien ouvert » ne signifie pas message envoyé ni livré.
7. [VÉRIFIÉ] Emploi du temps publié, certificats et une checklist d’onboarding sont présents ; la checklist s’arrête avant la configuration complète.
8. [VÉRIFIÉ] Le cloisonnement par école, policies et audit avant/après sont présents ; groupe multi-campus et export complet vérifiable restent à démontrer.
9. [HYPOTHÈSE] Le blocage d’adoption le plus directement prouvable est la mise en service/import, suivi du manque de pilotage synthétique prouvé.
10. [À CONFIRMER] Aucun test chronométré avec une école, mesure de charge à 2 000 élèves ni benchmark concurrent validé n’a été réalisé dans cet audit.

## Périmètre, méthode et limites

- [VÉRIFIÉ] L’inventaire est fondé sur `README.md`, routes Laravel, ressources/pages Filament, services, migrations et tests présents dans le dépôt. Les références ci-dessous pointent vers les preuves ; les chemins sont relatifs à la racine.
- [HYPOTHÈSE] Les nombres de clics/champs sont des estimations statiques de parcours (navigation + action), pas des mesures instrumentées. Ils doivent être chronométrés sur l’instance Render avec un utilisateur d’école.
- [À CONFIRMER] Aucune session authentifiée sur Render ni école pilote observée ; les scénarios décrivent les capacités de code, non une validation d’usage en production.
- [VÉRIFIÉ] L’environnement déclaré est Laravel 12, Filament 3, PHP 8.2+, avec PostgreSQL/Neon prévu et SQLite en tests (`composer.json`, `README.md`).
- [À CONFIRMER] Le calendrier officiel de l’année scolaire et les formats MEPST/DRE ne sont pas inférés ici.

## A.1 Inventaire fonctionnel vérifié

| Module | Capacités effectivement repérées | Limites constatées / à vérifier |
|---|---|---|
| Inscriptions / élèves | [VÉRIFIÉ] CRUD élève et formulaire d’inscription ; import élèves par commande CSV ; demandes publiques d’ouverture de compte directeur. `app/Filament/Resources/StudentResource.php:42-75,110-113`, `EnrollmentResource.php:39-71`, `app/Console/Commands/ImportStudentsCommand.php:8-21`, `app/Services/StudentCsvImporter.php:19-24,66-73,91-105`, `routes/web.php:22-23` | [VÉRIFIÉ] La ressource d’inscription n’a pas de route de création ; le CLI d’exemple n’envoie pas d’année scolaire à l’importeur. La colonne classe du CSV d’exemple ne crée donc pas une inscription. `EnrollmentResource.php:69-72`, `ImportStudentsCommand.php:10-21`, `StudentCsvImporter.php:97-105`, `docs/examples/students-import.csv:1-2`. Import de masse par écran non prouvé. |
| Présence | [VÉRIFIÉ] Création de session, synchronisation API, validation/verrouillage, historique, dashboard ; correction administrative auditée ; notifications mises en file après validation. `routes/web.php:28-40`, `app/Services/AttendanceService.php:59-107,146-164`, `app/Filament/Pages/AbsenceWorklist.php`, `app/Filament/Widgets/AttendanceOverview.php` | [VÉRIFIÉ] Le code décrit une synchronisation hors-ligne pour la présence, pas une garantie hors-ligne généralisée. La durée d’appel et l’usage sur téléphone Android modeste sont non mesurés. |
| Notes / bulletins | [VÉRIFIÉ] Saisie enseignant limitée aux affectations, import notes CSV avec aperçu/confirmation, règles de calcul, modèles de bulletin, étapes brouillon/revue/conseil/approbation/publication, PDF et versions. `app/Filament/Pages/GradeEntry.php:34-43,57-79,103-123`, `GradeImport.php:45-88`, `app/Services/ReportCardService.php:22-59,65-112,219-239`, `database/migrations/2026_09_08_000021_create_report_card_versions_table.php:11-20` | [À CONFIRMER] Le nom « conseil » dans une étape ne prouve pas la préparation d’un conseil de classe, le procès-verbal ou les règles de passage/redoublement. Aucun format officiel n’est affirmé. |
| Finance / caisse | [VÉRIFIÉ] Paiements, allocations, transactions, reçus, clés d’idempotence, annulations contrôlées, dépenses avec approbation et contre-passation, journal et clôture de caisse. `app/Filament/Resources/PaymentResource.php:38-53,73-90`, `app/Services/Payments/PaymentService.php:29-49,149-159`, `database/migrations/2026_09_08_000023_create_finance_tables.php:41-54,70-85,90-124`, `ExpenseResource.php:36-40,59-83`, `ExpenseService.php:54-65,71-99`, `app/Filament/Pages/CashJournal.php:22-56`, `CashJournalService.php:13-27` | [VÉRIFIÉ] Le formulaire accepte plusieurs méthodes ; le modèle n’est donc pas limité aux espèces, même si le lancement commercial est annoncé espèces. [À CONFIRMER] Rapprochement bancaire/mobile money et état comptable exportable non prouvés. |
| Communication | [VÉRIFIÉ] Relances de recouvrement/échéance planifiées ; email en file si configuration et adresse présentes ; WhatsApp Click-to-Chat ouvre un lien et trace l’ouverture ; le canal Twilio est explicitement réservé à une phase ultérieure. `routes/console.php:7-9`, `app/Console/Commands/ScheduleCollectionRemindersCommand.php:8-19`, `ScheduleDueRemindersCommand.php:10-17`, `app/Services/Notifications/EmailChannel.php:15-41`, `WhatsappClickToChatChannel.php:13-42`, `WhatsappTwilioChannel.php:7-12` | [VÉRIFIÉ] L’application ne peut pas déclarer qu’un message WhatsApp a été livré. Modèles d’emails par identifiant, consentement et historique unifié famille à vérifier par parcours avant d’en faire une promesse produit. |
| Documents | [VÉRIFIÉ] Certificats : demande, validation, délivrance, annulation et PDF. Reçus et cartes élèves ont des routes/contrôleurs. `CertificateResource.php:55-68`, `CertificateService.php:42-87`, `routes/web.php:58-59`, `app/Http/Controllers/StudentCardController.php` | [À CONFIRMER] Génération en masse, QR de vérification externe et portabilité complète non démontrés par les preuves accessibles. |
| Administration / exploitation | [VÉRIFIÉ] Années/périodes, classes, matières/configurations, affectations, utilisateurs, seuils et structure de frais ; checklist d’onboarding ; rôles `super_admin`, `director`, `teacher`, `accountant`. `README.md` (section « Fonctionnalités Sprint 1 »), `app/Filament/Pages/SchoolOnboarding.php:39-74` | [VÉRIFIÉ] La checklist suit école, année/périodes, classes, utilisateurs, élèves, mais pas tarif, matières ou emploi du temps. Censeur/surveillant n’est pas dans la liste des rôles annoncés. `SchoolOnboarding.php:39-74`, `README.md`. |
| Super admin / multi-école | [VÉRIFIÉ] Gestion des écoles, accès SaaS, intervention temporaire d’une heure journalisée, isolation tenant et refus inter-école testés. `app/Filament/Pages/SchoolSupport.php:31-38,79-116`, `app/Services/SuperAdminInterventionService.php:10-25`, `app/Models/Concerns/BelongsToSchool.php:11-62`, `tests/Feature/MultiTenancyTest.php:15-33` | [VÉRIFIÉ] Le modèle SaaS multi-école ne prouve pas une consolidation de groupe multi-campus avec comparaison de sites. |
| Emploi du temps | [VÉRIFIÉ] Créneaux créés/publiés ; visibilité du personnel et tests de conflit. `app/Filament/Resources/TimetableSlotResource.php:39-43,77-85`, `tests/Feature/TimetableWorkflowTest.php:20-43` | [À CONFIRMER] Construction par optimisation, présence enseignants, paie vacataires et impression multi-classes non prouvées. |

## A.2 Écarts par parcours (estimation statique, à chronométrer)

Convention : les comptes de clics et champs sont des **fourchettes de parcours minimal**, non des mesures en session. Les champs sont des éléments métier à renseigner (pas un comptage fiable des widgets Filament) ; le compte exact de formulaire demeure [À CONFIRMER]. « Ressaisie » ne compte comme certaine que si la donnée est séparée dans le modèle observé.

| Persona / tâche | Existant prouvé | Exigence métier | Friction, étapes/champs estimés | Gravité |
|---|---|---|---|---|
| Directeur — état argent/présence/résultats en 1 min | [VÉRIFIÉ] Widget de présence et centre d’action par rôle ; journal de caisse séparé ; workflows de résultats séparés. `AttendanceOverview.php`, `RoleActionCenter.php`, `CashJournal.php` | Vue synthèse par école/période avec date de fraîcheur et anomalies actionnables | [HYPOTHÈSE] 3 modules au minimum, 6-12 clics, 0 champ pour consulter ; chiffres à reconstituer mentalement, aucune vue consolidée « en 1 minute » prouvée. | Haute |
| Directeur — préparer rentrée / impayés | [VÉRIFIÉ] Checklist limitée ; relances planifiées ; structure de frais, factures et paiements. `SchoolOnboarding.php:39-74`, `ScheduleCollectionRemindersCommand.php:8-19` | Import/paramétrage guidé et liste des familles à action | Import CLI sans aperçu, puis config dispersée ; 3-6 écrans, environ 5-10 sélecteurs/valeurs [HYPOTHÈSE] ; données élève/responsable réintroduites dans le fichier, nombre de colonnes exact [À CONFIRMER]. `ImportStudentsCommand.php:8-21`. | Critique |
| Directeur — valider bulletins / passage | [VÉRIFIÉ] Circuit bulletin versionné avec approbation/publication. `ReportCardService.php:22-59,65-112,219-239` | Revue par classe, anomalies, décisions de fin d’année, correction tracée | 2-4 filtres [HYPOTHÈSE], 0 champ par validation simple ou 1 motif par correction ; clics selon volume ; passage non établi. | Haute |
| Censeur — emplois du temps / présence enseignants / conseil | [VÉRIFIÉ] Créneaux publiés, conflits testés ; pas de rôle censeur déclaré. `TimetableSlotResource.php:39-43,77-85`, `TimetableWorkflowTest.php:20-43`, `README.md` | Gestion par rôle dédié, présence enseignants, conseil préparé | Création/édition manuelle, 2-5 écrans, environ 4-8 valeurs par créneau (jour, horaires, classe, matière, enseignant, salle) [HYPOTHÈSE] ; présence enseignant et retard de saisie non prouvés. | Haute |
| Comptable — encaisser pendant affluence | [VÉRIFIÉ] Création de paiement, allocation, reçu, idempotence ; parcours finance testé. `PaymentResource.php:38-53,73-90`, `PaymentService.php:29-49`, `FinanceWorkflowTest.php:28-58` | Retrouver rapidement élève/famille, saisir montant/mode, affecter à dette, délivrer reçu | 3-6 valeurs métier estimées (payeur/enfant, montant, méthode, allocation, référence, note) [HYPOTHÈSE] ; nombre de clics et champs UI exacts non chronométrés. | Haute |
| Comptable — échéancier, relancer, clôturer | [VÉRIFIÉ] Rappels existent ; caisse du jour soumise par comptable puis approuvée directeur. `CashJournal.php:22-56` | Promesse négociée/rappel par date, relance avec résultat, écart de caisse expliqué | Promesse non prouvée ; future saisie demanderait 4-6 valeurs (montant, date, motif, canal, résultat) [HYPOTHÈSE] ; relance sort vers WhatsApp et retour manuel, livraison non confirmée. 3-6 étapes. | Critique |
| Secrétaire — inscriptions en masse, documents, statut enfant | [VÉRIFIÉ] Import par CLI, élèves/inscriptions, certificats, cartes. `ImportStudentsCommand.php:8-21`, `CertificateService.php:42-87`, `StudentCardController.php` | Prévisualiser un lot, erreurs par ligne, conversion sans ressaisie, réponse rapide | Import CLI ; 0 saisie web par ligne mais fichier à préparer/corriger hors app ; 5-10 associations de colonnes [HYPOTHÈSE], nombre exact de champs CSV non confirmé. Classe d’exemple non convertie en inscription. | Critique |
| Enseignant — appel en <30s, notes, appréciations, emploi du temps | [VÉRIFIÉ] Page d’appel, synchronisation hors-ligne, saisie de notes par affectation, import, appréciations et créneaux publiés. `AttendanceService.php:59-107`, `GradeEntry.php:34-43`, `TimetableSlotResource.php:77-85` | Ouvrir sa classe en 1 action, appel par exceptions, notes tableur, planning personnel | Appel : 1 classe/date + 0 à N statuts d’exception ; notes : une valeur par élève et 2-4 filtres [HYPOTHÈSE]. Durée <30 s et collage tableur non mesurés. | Haute |
| Parent — dette, reçu, absences, notes, contact | [VÉRIFIÉ] Documents générés et actions de notification de l’école ; aucune route de portail parent/enfant dans `routes/web.php:13-69`. | Accès privé et compréhensible sans créer de compte complexe | 0 champ dans l’app car pas de parcours parent prouvé ; sortie probable (appel/déplacement/WhatsApp/papier) avec ressaisie orale de l’identité. Risque tuteur/numéro partagé. | Haute |
| Super admin — activation école et support | [VÉRIFIÉ] Création école, intervention et checklist. `SchoolSupport.php:31-116`, `SchoolOnboarding.php:39-74` | Faire passer l’école à sa première valeur sans support développeur | Paramétrage multi-écrans et import CLI ; 5-10 valeurs de configuration [HYPOTHÈSE], TTFV non mesuré. | Critique |

### Mesure terrain à faire

Chronométrer 5 utilisateurs par persona dans l’environnement pilote ; relever le premier accès, nombre de clics, champs réellement saisis, erreurs, demandes d’aide, temps hors réseau et retours papier/WhatsApp. Ne pas remplacer cette mesure par les fourchettes statiques du tableau.

## A.3 Écarts structurels

| Dimension | État prouvé | Conséquence |
|---|---|---|
| Historisation | [VÉRIFIÉ] `AuditLog` stocke acteur, action, valeurs avant/après et métadonnées de requête ; les bulletins ont des versions. `2026_09_08_000011_create_audit_logs_table.php:10-23`, `Auditable.php:9-37`, migration `000021` | Bonne fondation ; vérifier la couverture de chaque événement métier, export et rétention. |
| Événements/statistiques | [VÉRIFIÉ] Audit des événements de modèles, notifications et journaux métier, mais pas preuve d’un bus d’événements analytique ou d’un dictionnaire d’événements stable. | Instrumenter peu d’événements utiles et documentés avant alertes/analytique. |
| Multi-campus/groupe | [VÉRIFIÉ] `school_id` / isolation par école ; aucune preuve de campus distincts sous une entité groupe. `BelongsToSchool.php:11-62` | Éviter de confondre SaaS multi-tenant avec reporting consolidé multi-site. |
| Années/périodes | [VÉRIFIÉ] Année scolaire propre à l’école et périodes associées ; année courante unique. `2026_09_08_000002_create_academic_years_table.php:10-22`, `AcademicYear.php:18-34` | Vérifier historique de classe/transfert des élèves et données affichées après clôture. |
| Familles | [VÉRIFIÉ] Élèves et responsables existent ; absence de preuve d’un compte famille multi-enfants, relation plusieurs tuteurs avec droits granulaires ou solde consolidé. | Un même numéro ne doit pas servir de clé d’identité ; modèle familial à valider avant portail. |
| Règles métier | [VÉRIFIÉ] Ensembles de règles d’évaluation et seuils mention existent. [À CONFIRMER] Configurabilité réellement administrable, historique de version et couverture de toutes les règles de passage non prouvés. `EvaluationRuleSet` / `MentionThresholdResource.php`. | Tester règles par niveau et conserver la version appliquée à chaque publication. |
| Export/portabilité | [VÉRIFIÉ] Exports/CSV de certains parcours et PDF présents ; pas de preuve d’un export complet restaurable école. `routes/web.php:58-59`, ressources d’import | Définir un manifeste d’export, journal de téléchargement et test de réimport. |
| Charge 2 000+ élèves / pics | [À CONFIRMER] Aucune preuve d’un test de charge ou d’une mesure de latence, de concurrence, de queue ou de verrous DB à cette échelle. | Test PostgreSQL et scénarios de pic requis ; ne pas conclure que le code ne tient pas, ni promettre qu’il tient. |
| Idempotence / reprise | [VÉRIFIÉ] Clés idempotentes dans le financement et sync de présence. [À CONFIRMER] Toute opération d’import, paiement et offline n’a pas été vérifiée sur coupure/conflit. | Étendre seulement avec tests de reprise et états de conflit explicitement visibles. |

## A.4 Calendrier annuel par moments (dates à confirmer chaque année)

Les périodes sont des repères métier, pas des dates officielles. [À CONFIRMER] Valider le calendrier annuel auprès du MEPST/DRE et de chaque école.

| Moment de vérité | Directeur / censeur | Comptable / secrétaire | Enseignant / parent | Scolaris fournit aujourd’hui |
|---|---|---|---|---|
| Avant rentrée / réinscriptions | Préparer classes, personnel, frais et capacité ; vérifier admissions | Import, échéanciers, caisse d’ouverture | Parents demandent frais et place | Année, classes, élèves, responsables, import CSV CLI, frais ; checklist partielle. |
| Rentrée / forte affluence | Voir inscrits, présents et encaissements rapidement | Retrouver élève, encaisser, reçu, clôture | Professeur appel ; parent veut justificatif | Paiement/reçu/journal de caisse et présence existent ; TTFV/import restent risqués. |
| Compositions et saisie | Détecter classes/matières en retard | — | Saisir les notes, gérer absences, corriger | Saisie notes et import CSV ; pas de preuve de dashboard de retard par enseignant. |
| Conseil / fin de période | Examiner cas, décider et tracer | Suivre soldes liés aux résultats si politique de l’école | Appréciations et relevés | Workflow bulletin et snapshots ; assistance au conseil et procès-verbal non établis. |
| Publication des bulletins | Valider les versions et éviter une publication prématurée | Préparer relances | Parent consulte/récupère bulletin | Workflow d’approbation/publication et PDF ; pas de portail parent prouvé. |
| Examens blancs / examens nationaux | Liste candidat, dossier, décision de niveau | Frais de dossier éventuels | Élèves, familles et enseignants suivent les dates | Aucune preuve de module candidats/exports officiels ; formats à confirmer, aucun format inventé ici. |
| Vacances / clôture annuelle | Décision de passage, transfert, archivage | Clôture caisse, rapports de l’année | Parent veut résultat et prochaine classe | Année scolaire et versions de bulletin ; décisions et clôture annuelle de dossiers à confirmer. |
| Relances avant/après échéance | Suivre risques et exceptions | Segmenter débiteurs, relancer, enregistrer promesse | Tuteur clarifie le solde | Relances planifiées et canaux ; promesses et résultat de contact à confirmer. |

**Moment de vérité à risque** : [HYPOTHÈSE] rentrée, car import de volume et encaissement se cumulent ; fin de trimestre, car saisie/validation/publication sont simultanées ; clôture de caisse, car les écarts exigent une explication immédiate. Ces risques doivent être testés sur pilote.

## A.5 Adoption et frictions prioritaires

- [VÉRIFIÉ] La checklist initiale couvre seulement école, année/périodes, classes, utilisateurs et élèves ; le paramétrage matières, frais et horaires n’y est pas guidé. `app/Filament/Pages/SchoolOnboarding.php:39-74`.
- [VÉRIFIÉ] L’import élève est une commande artisan, pas un parcours secrétaire ; la liaison classe/année de l’exemple n’est pas réalisée par le chemin de commande. `app/Console/Commands/ImportStudentsCommand.php:8-21`, `app/Services/StudentCsvImporter.php:97-105`.
- [HYPOTHÈSE] Temps actuel jusqu’à la première valeur : **1 à 5 jours ouvrés** avec assistance, selon volume/qualité des données ; à chronométrer. Cible proposée : **≤ 1 jour ouvré** jusqu’à première inscription correcte et premier encaissement de test, sur import de 2 000 élèves simulés.

| Rang | Identifiant / friction | Impact probable | Preuve ou statut |
|---:|---|---|---|
| 1 | GAP-ONB-01 — import non guidé et classe non convertie en inscription via le chemin d’exemple | Rentrée retardée, ressaisie | [VÉRIFIÉ] CLI et paramètres absents ci-dessus. |
| 2 | GAP-DIR-01 — absence de synthèse de direction prouvée argent + assiduité + résultats | Décisions lentes, support avant vente | [HYPOTHÈSE] Surfaces séparées dans pages/widgets. |
| 3 | GAP-REC-01 — promesse de paiement et historique de contact famille non prouvés | Impayés non suivis / relance doublée | [À CONFIRMER] Parcours à tester dans `CollectionCenter.php` et notifications. |
| 4 | GAP-FAM-01 — pas de vue solde fratrie prouvée | Parent ne comprend pas montant total, relance par enfant | [À CONFIRMER] Modèle de famille à vérifier avant conception. |
| 5 | GAP-TEA-01 — appel <30 s et mode exception non mesurés | Abandon de saisie aux heures de pointe | [À CONFIRMER] sync existe, chronométrage absent. |
| 6 | GAP-PED-01 — conseil assisté et décision de passage non démontrés | Travail Excel/cahier en fin d’année | [À CONFIRMER] workflow de bulletin existe, mais périmètre limité. |
| 7 | GAP-PORT-01 — aucune consultation parent privée prouvée | Appels/déplacements au secrétariat | [VÉRIFIÉ] Pas de route portail dans `routes/web.php:13-69`. |
| 8 | GAP-CLOSE-01 — écart de caisse explicable et rapport fondateur non mesurés | Litiges, perte de confiance | [VÉRIFIÉ] clôture existe ; qualité du rapport et gestion d’écart à éprouver. |
| 9 | GAP-EXPORT-01 — export complet/restore non prouvé | Verrouillage fournisseur, risque de confiance | [À CONFIRMER] exports partiels visibles seulement. |
| 10 | GAP-LOAD-01 — capacité à 2 000+ et sous pic non prouvée | Échec à l’instant de vérité | [À CONFIRMER] pas de résultats de charge dans les tests consultés. |

## Décisions de cadrage proposées

1. Résoudre l’import/paramétrage avant d’ajouter une vitrine parent : le premier client doit atteindre sa première valeur sans CLI.
2. Exploiter la finance/caisse déjà existante plutôt que refaire un module : améliorer le suivi des promesses et la lecture des écarts.
3. Garder toutes les communications honnêtes : lien WhatsApp généré/ouvert, jamais « envoyé » ou « livré ».
4. Mesurer les durées sur téléphone réel et réseau dégradé avant de promettre le hors-ligne au-delà de l’appel.
5. Traiter 2 000 élèves comme exigence de test pilote, pas comme capacité déjà garantie.
