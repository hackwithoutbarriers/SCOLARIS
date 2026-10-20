# Phase 2 — Matrice des accès

1. La matrice couvre six rôles scolaires/plateforme et a un test de visibilité role × ressource pour les 24 ressources Filament enregistrées.
2. Les comptes scolaires sont limités à une école ; les enseignants sont limités aux classes/matières affectées pour les notes et bulletins.
3. Les dossiers nominatifs élèves/familles sont réservés au directeur et au secrétaire ; le censeur accède aux listes opérationnelles de présence et aux lectures académiques, sans finance ni modification des notes.
4. Le comptable dispose des opérations financières prévues, sans suppression ou modification directe d’un paiement ; le directeur publie les bulletins et peut reverser avec motif.
5. Le super administrateur gère la plateforme ; les données d’école sont accessibles uniquement dans une intervention temporaire, école-scopée et journalisée.
6. Les actions sensibles, exports, reçus, bulletins PDF, cartes, liens signés et points d’entrée API ont des tests d’autorisation/portée documentés ci-dessous.
7. L’ouverture, la fermeture manuelle et l’expiration d’intervention sont auditées ; les contextes expirés ou incomplets sont refusés et effacés.
8. La désactivation invalide la session ; l’édition de rôle/activation est auditée et exige une intervention ciblée ; les invitations ne déplacent pas les comptes d’école.
9. La connexion téléphonique est facultative, normalisée et unique ; les collisions après normalisation et le rollback dangereux sont refusés par test.
10. Aucun blocage connu ne justifie de conserver la Phase 2 ouverte ; le détail des limites de vérification résiduelles figure à la fin.

## Légende

| Code | Droit |
|---|---|
| `L` | Lire et lister dans le périmètre autorisé |
| `C` | Créer |
| `M` | Modifier |
| `V` | Valider, approuver ou publier selon le workflow |
| `R` | Reverser, uniquement avec motif et contrôle du directeur |
| `E` | Exporter ou télécharger |
| `—` | Aucun accès |
| `affect.` | Classes/matières affectées à l’enseignant |
| `interv.` | École sélectionnée dans une intervention temporaire et auditée |

Les droits combinés correspondent à plusieurs opérations. Les actions financières sont précisées séparément lorsqu’un droit de lecture ne vaut pas autorisation d’écriture.

## Matrice cible

| Surface / action | Directeur | Enseignant | Comptable | Censeur | Secrétaire | Super admin |
|---|---|---|---|---|---|---|
| Tableau de bord général | L | L | L | L | L | L |
| Indicateurs académiques complets | L | — | — | L | — | interv. |
| Indicateurs financiers | L | — | L | — | — | interv. |
| Dossiers nominatifs élèves et responsables | LCM | — | — | — | LCM | interv. |
| Import élèves (lots, erreurs, fichiers et rapports) | LCMV | — | — | — | LCMV | interv. journalisée |
| Élèves des classes / listes de présence | LCM | L affect. | — | L école | LCM | interv. |
| Inscriptions / réinscriptions | LCM | — | — | — | LCM | interv. |
| Certificats et registre de délivrance | LCM/E | — | — | — | LC/E | interv. |
| Années, périodes, classes, matières, paramètres scolaires | LCM | — | — | — | — | interv. |
| Affectations des enseignants | LCM | L affect. | — | — | — | interv. |
| Emploi du temps | LCM | L | L | L | L | interv. |
| Évaluations | LCM | L affect. | — | L | — | interv. |
| Notes — consultation | L | L affect. | — | L | — | interv. |
| Notes — saisie / modification | LCM | CM affect. (avant validation) | — | — | — | interv. |
| Notes — import | LC (école) | — | — | — | — | interv. |
| Bulletins — consultation / PDF | L/E | L/E affect. | — | L/E | — | interv. |
| Bulletins — soumission | L | V affect. | — | — | — | interv. |
| Bulletins — approbation / publication | V | — | — | — | — | interv. |
| Présence — saisie / synchronisation | LCM école | LCM affect. | — | L | — | interv. |
| Présence — validation / correction contrôlée | V école | V affect. | — | V école | — | interv. |
| Suivi des absences / justification / contact famille | LCM | L affect. | — | LCM école | LCM | interv. |
| Factures et débiteurs | L | — | LCM | — | — | interv. |
| Encaissements et reçus | L/E | — | LC/E | — | — | interv. |
| Dépenses et journal de caisse | L/V | — | LCM | — | — | interv. |
| Reversal d’un paiement | R (motif obligatoire) | — | — | — | — | interv. (journalisé) |
| Demandes d’accès / comptes plateforme | — | — | — | — | — | LCM |
| Invitations et comptes du personnel de l’école | LCM (école) | — | — | — | — | interv. (école sélectionnée) |
| Écoles et abonnements | — | — | — | — | — | LCM |
| Données d’une école via assistance | — | — | — | — | — | interv. (lecture, écriture ou export auditée) |
| Invitation de personnel à une école | LCM école | — | — | — | — | interv. (école cible, événement dédié audité) |

Les règles sur les paramètres réglementaires et les textes demeurent soumises à validation métier ; la matrice n’autorise pas à inventer ou figer leurs valeurs.

## Implémentation et écarts constatés

| ID | Sévérité | Écart / preuve | Mesure appliquée ou requise |
|---|---|---|---|
| P2-ACL-01 | Majeur | `Gate::before` et plusieurs `before()` de policies accordaient au super administrateur un accès global sans intervention ; l’assistance affichait aussi les métriques sans contexte temporaire. | Corrigé : contournement global retiré ; autorisations scolaires limitées à l’école active ; toutes les requêtes web en intervention journalisent route et méthode ; métriques masquées hors contexte. |
| P2-ACL-02 | Majeur | `AcademicPolicy::viewGrades` autorisait le secrétaire à lire les notes ; `GET /api/academic/students/{student}/grades` s’appuie directement sur cette policy. | Corrigé : secrétaire refusé ; test HTTP 403 ajouté. |
| P2-ACL-03 | Majeur | Les pages `GradeEntry` et `MissingGrades` étaient réservées aux enseignants, contrairement aux droits académiques du directeur ; le contrôle existant échouait sur `/admin/grade-entry`. | Corrigé : directeur autorisé, tests de navigation conservés. |
| P2-ACL-04 | Majeur | Les policies de notes confondaient la lecture avec `manageGrades`, et le censeur n’avait pas de rôle académique en lecture. | Corrigé : lecture censeur autorisée dans son école ; création et modification restent refusées. |
| P2-ACL-05 | Majeur | Le centre d’actions utilisait la branche « directeur » pour les rôles non enseignant/non comptable et affichait alors les impayés à des rôles sans finance. | Corrigé : branches censeur et secrétaire sans métrique financière ; indicateurs académiques masqués au personnel non autorisé. |
| P2-ACL-06 | Majeur | Le censeur ne pouvait pas accéder à l’API de présence, au suivi des absences, aux suivis de bulletins ni contacter les responsables pour les absences. | Corrigé sur les surfaces identifiées ; test de workflow censeur ajouté. |
| P2-ACL-07 | Mineur | Le censeur n’était pas proposé dans les formulaires d’invitation et d’administration. | Corrigé : option ajoutée dans les deux parcours. |
| P2-ACL-08 | Majeur | Le comptable pouvait demander et exécuter un reversal d’après `PaymentPolicy::reverse` et `PaymentService::reverse`. | Corrigé : directeur seul (ou super administrateur en intervention auditée), motif obligatoire ; test de refus comptable ajouté. |
| P2-ACL-09 | Majeur | Le point d’entrée de notes d’un enseignant exposait les résultats agrégés de toutes les matières de l’élève, même lorsque certaines évaluations appartenaient à un autre enseignant. Le filtre supposait aussi une configuration de matière propre à une classe. | Corrigé : l’API et la policy rattachent les notes à l’affectation de l’enseignant pour la classe et l’année de l’inscription ; un test couvre deux enseignants et une configuration annuelle commune aux classes. La mention générale n’est pas calculée à partir des notes partielles. |
| P2-ACL-10 | Majeur | La matrice n’est pas encore éprouvée sur chaque cellule rôle × ressource × action ; certains liens signés et téléchargements restent sans test inter-écoles dédié. | **À compléter :** inventaire et tests systématiques des surfaces restantes et des parcours de cycle de vie. Les actions de suppression Filament sont retirées et les téléchargements PDF de carte sont désormais journalisés comme exports. |
| P2-ACL-11 | Majeur | `StudentRegistryController::csv()` et `pdf()` refusaient l’accès au super administrateur même en intervention ; la requête CSV aurait en outre été classée comme simple lecture dans `ApplySchoolInterventionContext`. | Corrigé : export autorisé seulement quand le contexte actif correspond à l’école ; les extensions CSV/Excel sont journalisées comme export ; test de portée inter-écoles ajouté. |
| P2-ACL-12 | Majeur | `PaymentPolicy::update()` et `delete()` accordaient une autorisation financière générale (notamment suppression au directeur ou au super administrateur en intervention), alors que la correction doit préserver le paiement original. | Corrigé : modification et suppression interdites, y compris en intervention super administrateur ; correction seulement par reversal avec motif ; tests sur comptable, directeur et super administrateur en intervention. |
| P2-ACL-13 | Majeur | `AcademicPolicy::viewGrades()` et le filtre Filament des bulletins enseignant vérifiaient une affectation à la classe sans exiger la même année scolaire que le bulletin ou l’inscription. | Corrigé : les notes API et bulletins visibles doivent correspondre à une affectation dans l’année de l’inscription/bulletin ; tests HTTP, policy et liste Filament couvrent l’année antérieure non affectée. |
| P2-ACL-14 | Majeur | Douze ressources Filament exposaient une `DeleteBulkAction` sur des dossiers scolaires et paramètres sans parcours de suppression validé. | Décision confirmée : suppressions groupées désactivées, dossiers conservés ; aucun `DeleteBulkAction` ni `DeleteAction` restant dans les ressources Filament. Test couvrant les douze listes. |
| P2-ACL-15 | Majeur | Les policies `AcademicPolicy::delete()` et `SecretaryResourcePolicy::delete()` autorisaient encore en théorie la suppression unitaire d’élèves, responsables ou inscriptions, bien que les boutons Filament aient été retirés. | Décision confirmée : interdiction au niveau des policies pour les dossiers élève/famille/inscription ; tests directeur et secrétaire. |
| P2-ACL-16 | Majeur | Les liens signés WhatsApp/e-mail embarquaient les variables rendues (noms, date, montant, lien bulletin) et l’identifiant élève dans leur query string ; les journaux de notification conservaient également les variables en clair. | Corrigé : liens signés limités au responsable, modèle et identifiants de contexte minimaux ; variables reconstruites depuis les enregistrements autorisés ; payload des journaux limité à des métadonnées sans contenu personnel ; affectation enseignant de l’année contrôlée. |
| P2-ACL-17 | Mineur | Les exports PDF de cartes scolaires portaient le nom de route `student-cards.*`, qui n’était pas reconnu par le classificateur d’audit d’intervention et était donc journalisé comme lecture. | Corrigé : routes de cartes scolaires classées comme exports ; test PDF avec intervention active. |
| P2-ACL-18 | Majeur | Le téléchargement PDF de carte échouait en 500 : `StudentCardService` passait des arguments nommés au constructeur `Endroid\QrCode\Builder\Builder`, dont le constructeur installé ne les accepte pas. | Corrigé : construction du QR via l’API fluide de la dépendance ; test d’intervention vérifie le PDF 200 et son journal d’export. |
| P2-ACL-19 | Majeur | Les endpoints de classes/élèves de présence validaient l’affectation enseignant par classe sans vérifier que l’année de l’affectation correspond à l’année de la classe. | Corrigé : liste des classes et lecture des élèves conditionnées par classe + année scolaire ; test avec affectation incohérente. |
| P2-ACL-20 | Critique | `StaffInvitationService::create()` acceptait tout `school_id` d’un super administrateur sans intervention ; la page d’invitation montrait toutes les écoles. `UserResource` proposait aussi le rôle `super_admin` et une liste globale d’écoles pendant l’intervention. | Corrigé : intervention obligatoire et école cible identique pour les invitations aux écoles existantes ; création du directeur lors de l’onboarding plateforme reste permise par un chemin explicite limité au rôle directeur et est auditée. Le choix d’école en intervention est limité à l’école active et le formulaire ne propose plus le rôle super administrateur. L’approbation de demandes pour une école existante suit la même garde ; tests service et accès ajoutés. |
| P2-ACL-21 | Majeur | L’édition d’un compte dans `UserResource` pouvait changer le rôle ou l’activer/désactiver sans trace dédiée ; la mise à jour ne réaffirmait pas explicitement l’école de l’intervention. | Corrigé : `EditUser::handleRecordUpdate()` impose l’intervention sur l’école exacte, exclut toute réaffectation scolaire et journalise dans la même transaction les changements de rôle/activation, sans secret ni donnée personnelle. Test de modification autorisée, refus hors intervention et refus d’un compte d’une autre école. |
| P2-ACL-22 | Majeur | La règle de validation téléphone de `UserResource` était transmise comme fermeture directement à Filament, provoquant une erreur 500 lors du rendu du formulaire utilisateur. | Corrigé : règle enveloppée dans `Illuminate\Validation\ClosureValidationRule`; le test du cycle de vie charge et soumet maintenant le formulaire complet. |

## Inventaire des surfaces vérifiées

| Surface | Ressources / pages / endpoints | Preuves de rôle et de portée |
|---|---|---|
| Ressources Filament | Année scolaire, évaluation, certificat, classe, libellé de conduite, inscription, dépense, frais, note, responsable, seuil de mention, paiement, demande d’accès, bulletin, modèle de bulletin, école, abonnement, élève, configuration de matière, matière, affectation enseignant, période, emploi du temps, utilisateur. | `RoleNavigationVisibilityTest` compare les 24 ressources pour chacun des six rôles, puis super administrateur sans et avec intervention. |
| Pages Filament | Tableau de bord, saisie/import/suivi de notes, notes manquantes, suivi des bulletins, liste d’absences, centre de collecte, journal de caisse, onboarding école et assistance super administrateur. | `RoleNavigationVisibilityTest`, `DashboardRoleActionsTest`, `SuperAdminDashboardTest`, `SuperAdminInterventionAccessTest`. Accès direct aux pages sensibles vérifié en plus de la navigation. |
| Widgets | Centre d’actions, vues académiques, présence, finance, statistiques école et assistance/plateforme. | `RoleNavigationVisibilityTest` vérifie les droits académiques/présence/finance par rôle ; `SuperAdminInterventionAccessTest` vérifie l’absence de métriques avant intervention. |
| Données académiques et présences | Notes API, import, présence API (lecture, saisie, synchronisation, validation, historique, tableau de bord), HTML/PDF des bulletins. | `AcademicAuthorizationTest`, `TeacherGradeScopeTest`, `GradeImportTest`, `AttendanceWorkflowTest`, `TenantIsolationAttendanceTest`, `ReportCardWorkflowTest`. Les affectations sont croisées avec l’année scolaire ; les accès école voisine sont refusés. |
| Finance et sorties | Encaissement API, débiteurs, rapports CSV, reçu PDF, reversal, dépenses et décisions d’approbation. | `FinanceSecurityTest`, `FinanceExportAuthorizationTest`, `FinanceWorkflowTest`, `ExpenseWorkflowTest`. Les paiements d’une autre école sont absents/refusés, les rôles non financiers sont refusés et le reversal est réservé au directeur avec motif. |
| Registre, cartes et notifications | Exports PDF/CSV du registre, cartes PDF individuelles et par classe, liens signés WhatsApp/e-mail, webhook paiement. | `SuperAdminInterventionAccessTest`, `NotificationScopeTest`, `FinanceSecurityTest`, `HttpContractsTest`. Export intervention audité/école-scopé, liens signés sans identifiants personnels, webhook HMAC. |
| Accès et cycle de vie | Connexion e-mail/téléphone, inscription publique, approbation, invitation à usage unique, rôle/activation, expiration d’intervention. | `AuthenticationTest`, `PhoneSignInMigrationTest`, `RegistrationApprovalTest`, `StaffInvitationTest`, `UserAccessLifecycleTest`, `SuperAdminInterventionAccessTest`. |

## Tests de non-régression ajoutés

| Test | Garantie |
|---|---|
| `RoleNavigationVisibilityTest` | Pages accessibles au censeur, lecture seule des notes, refus du finance ; accès directeur aux pages académiques. |
| `AttendanceWorkflowTest` | Le censeur peut traiter la présence dans toute son école et enregistrer un suivi d’absence ; l’enseignant reste limité à ses affectations. |
| `SecretaryRoleTest` | Le secrétaire ne peut pas lire les notes via l’API, même pour un élève inscrit dans son école. |
| `AcademicAuthorizationTest` | Les notes de l’API et les écritures d’évaluations sont limitées à l’affectation de l’enseignant ; les élèves d’une autre école restent interdits. |
| `SuperAdminInterventionAccessTest` | Refus hors intervention et après expiration ou sans échéance, portée école, journalisation lecture/export/expiration et absence de métriques avant intervention. |
| `FinanceWorkflowTest` | Le reversal est refusé au comptable et reste réservé au directeur avec motif ; paiement immuable (update/delete refusés aux rôles métier et au super administrateur en intervention). |
| `FinanceExportAuthorizationTest` | Le rapport financier et les reçus PDF sont réservés aux rôles financiers et aux enregistrements de leur école ; aucun élève d’une école voisine dans l’export CSV. |
| `BulkDeletionAuthorizationTest` | Les douze listes de ressources scolaires n’exposent plus l’action groupée de suppression ; suppression des élèves, responsables et inscriptions refusée au directeur et au secrétaire. |
| `NotificationScopeTest` | Le lien signé n’expose ni variables personnelles ni identifiant élève ; les variables sont reconstruites côté serveur, l’accès contact reste lié au dossier concerné et les journaux ne conservent pas les noms. |
| `HttpContractsTest` | Un compte désactivé reçoit un refus, est déconnecté et sa session est invalidée. |
| `StaffInvitationTest` | Acceptation d’invitation téléphone seul et connexion par numéro, connexion par e-mail préservée, impossibilité de déplacer un compte entre écoles, invitations super administrateur limitées à l’intervention et formulaire utilisateurs limité à l’école active sans rôle super administrateur. |
| `RoleNavigationVisibilityTest` | Le parcours de démarrage dirige le directeur vers la page d’invitations accessible, pas vers l’administration plateforme des comptes. |
| `RegistrationApprovalTest` | L’approbation d’une demande pour une école existante par le super administrateur est refusée sans intervention ciblée et permise dans le contexte correspondant. |
| `PhoneSignInMigrationTest` | La migration refuse les numéros dupliqués après normalisation et refuse son retour arrière si des utilisateurs sans e-mail existent. |
| `UserAccessLifecycleTest` | L’édition d’un compte exige l’école exacte de l’intervention ; les changements rôle/activation sont auditables et atomiques ; l’URL d’un compte d’une autre école donne 404. |
| `StudentImportAccessTest` | Import réservé au directeur/secrétaire; refus comptable/enseignant; les lots et lignes d’une autre école sont refusés par policy/service et l’URL directe d’un lot donne 404. |

## Limites de preuve

- La matrice automatisée compare les droits de visibilité des ressources Filament ; les états par enregistrement et les transitions de chaque workflow restent couverts par leurs tests dédiés, pas par un unique test combinatoire universel.
- Les tests E2E navigateur et le parcours visuel Android (320–1280 px) appartiennent à la Phase 4/3 et n’ont pas été exécutés dans ce lot ACL.
- La signature entrante Twilio reste à vérifier avant toute réactivation de ce fournisseur ; ce point concerne la mise en service du canal, désactivé par défaut, et non l’ACL du webhook de paiements déjà protégé par HMAC.

## Décision de clôture

La Phase 2 est clôturée pour le périmètre ACL inventorié : les ressources Filament sont vérifiées par rôle, les lectures/écritures et téléchargements sensibles ont des tests ciblés, l’isolation inter-écoles et l’intervention temporaire sont éprouvées, et les changements de rôle/activation sont tracés. Les tests visuels/E2E et la signature Twilio restent des tâches distinctes des ACL et sont reportés aux phases appropriées.

### Addendum IDEA-ONB-01

La policy `StudentImportBatchPolicy` autorise Directeur/Secrétaire de leur école et limite le Super Admin à l’intervention temporaire; `StudentImportRowPolicy` hérite du périmètre du lot. La page, le rapport et le traitement vérifient également le lot courant. `StudentImportAccessTest` couvre ces rôles et l’URL directe d’un lot voisin. Le test de revue d’accès du rapport téléchargé et l’essai sur PostgreSQL restent à exécuter avant production pilote.
