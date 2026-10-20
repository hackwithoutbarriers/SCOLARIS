# 01 — Personas, JTBD et moments de vérité

## Résumé en 10 lignes

1. [HYPOTHÈSE] L’acheteur prioritaire est le fondateur/directeur qui veut gagner en contrôle sans acheter du sur-mesure.
2. [HYPOTHÈSE] La douleur économique la plus chère est le retard de recouvrement et le temps de suivi manuel.
3. [VÉRIFIÉ] Le comptable dispose déjà de paiements, reçus, journal et clôture ; la valeur attendue est la vitesse et la traçabilité.
4. [VÉRIFIÉ] L’enseignant dispose d’un appel et d’une saisie de notes ; la valeur dépend du nombre d’actions et de la confiance hors-ligne.
5. [VÉRIFIÉ] La secrétaire peut s’appuyer sur inscriptions, documents et import CSV CLI ; l’import non guidé freine l’autonomie.
6. [VÉRIFIÉ] Le parent n’a pas de route de portail établie ; il est bénéficiaire indirect, pas une hypothèse d’acheteur.
7. [HYPOTHÈSE] L’école recommande Scolaris si elle voit sa première valeur en une journée et si le personnel ne fait pas une double saisie.
8. [À CONFIRMER] Les écoles pilotes existent selon le porteur, mais leur disponibilité, profils, taille et calendrier d’entretien ne sont pas précisés.
9. [À CONFIRMER] Le modèle économique visé est l’abonnement par école ; aucun prix cible ni budget d’exploitation n’est fixé (budget actuel déclaré : 0).
10. [À CONFIRMER] La capacité hebdomadaire de développement est inconnue ; effort exprimé en jours, jamais en dates calendaires.

## Carte des rôles d’achat et d’usage

| Catégorie | Persona | Job-to-be-done payé / recommandé | KPI de valeur |
|---|---|---|---|
| Acheteur | Fondateur propriétaire / directeur | [HYPOTHÈSE] « Je dois savoir si l’école est saine et où agir sans être sur place toute la journée. » | Taux d’encaissement à J+30 ; temps hebdomadaire à produire l’état de gestion. |
| Utilisateur quotidien | Comptable / économe | « Je veux saisir chaque paiement une seule fois, remettre un reçu et fermer la caisse sans conflit. » | Durée médiane d’encaissement ; minutes de clôture ; % écarts expliqués. |
| Utilisateur quotidien | Secrétaire | « Je veux traiter une rentrée en masse et répondre sans fouiller dans des cahiers. » | Temps import→première inscription correcte ; taux d’erreurs/ressaisies. |
| Utilisateur quotidien | Enseignant | « Je veux faire l’appel et saisir mes notes sans perdre la classe ni mes données. » | Appel médian ; % séances validées le jour même ; taux de correction. |
| Bénéficiaire indirect | Parent / tuteur | « Je veux comprendre le solde, l’échéance et la situation de mon enfant sans me déplacer. » | Appels/déplacements au secrétariat ; délai entre publication et consultation. |

## Fiches persona

### PERS-DIR-01 — Fondateur / directeur (acheteur, utilisateur de pilotage)

- **Pourquoi il paierait** : [HYPOTHÈSE] éviter que l’argent dû soit oublié, limiter les rapports reconstruits manuellement, accéder à un état clair quand il n’est pas sur place.
- **Trois moments de vérité** : rentrée (effectifs/encaissements), fin de trimestre (notes/publi), clôture annuelle (décisions/soldes).
- **Sans l’application** : [À CONFIRMER] cahier, tableur, appels au comptable ; durée et taux d’erreur à observer.
- **Craintes** : fausse impression de contrôle, fraude non visible, exposition des données des mineurs, dépendance à l’agent.
- **KPI** : temps pour obtenir un état consolidé inférieur à 5 minutes ; écart de caisse expliquée à J+1 ; recouvrement à J+30.
- **Preuve d’existant** : pages séparées de caisse, action-center, présence et résultats ; aucune synthèse consolidée attestée dans l’inventaire (`CashJournal.php`, `RoleActionCenter.php`, `AttendanceOverview.php`).

### PERS-DIR-02 — Fondateur non présent sur site

- **Pourquoi il recommanderait** : accès distant fiable à des totaux horodatés, sans prétendre contrôler une livraison WhatsApp ni accéder au détail médical/personnel inutile.
- **Moments** : ouverture/fermeture de caisse, absence inhabituelle, approbation de dépenses.
- **Sans l’app / durée** : [À CONFIRMER] appels, photos de cahier et WhatsApp ; mesurer nombre d’allers-retours.
- **Craintes** : compte partagé, journal falsifiable, connexion intermittente, fuite inter-écoles.
- **KPI** : délai entre clôture soumise et lecture/validation ; % actions sensibles avec acteur et justification.
- **Validation** : [VÉRIFIÉ] intervention SaaS admin temporaire auditée ; cela n’équivaut pas à un rôle propriétaire distant distinct.

### PERS-CEN-01 — Censeur / directeur des études

- **Pourquoi il recommanderait** : éviter conflits d’emploi du temps et obtenir à temps absences, notes et dossiers du conseil.
- **Moments** : rentrée (planning), composition/conseil (données complètes), exam/blocage de saisie.
- **Sans l’app** : [À CONFIRMER] planning tableur/papier, messages aux enseignants ; temps de correction de conflit à mesurer.
- **Craintes** : être responsable d’un planning erroné, manque de délégation/permissions.
- **KPI** : conflits détectés avant publication ; % notes saisies à échéance ; durée de préparation du conseil.
- **Preuve** : créneaux et tests de conflit présents ; rôle censeur non annoncé dans les rôles du README.

### PERS-CPT-01 — Comptable / économe

- **Pourquoi il resterait** : moins de saisie répétée et de contestations de solde, clôture défendable.
- **Moments** : affluence de rentrée, échéance de paiement, clôture quotidienne/mensuelle.
- **Sans l’app** : [À CONFIRMER] cahier de caisse et reçu papier en parallèle ; compter erreurs de rapprochement et minutes par encaissement.
- **Craintes** : blâme pour un écart qui n’est pas le sien, lenteur réseau, directeur modifiant un paiement sans trace.
- **KPI** : médiane d’encaissement ≤ 60 s (cible pilote) ; clôture ≤ 10 min ; 100 % annulations motivées.
- **Preuve** : paiements, reçus, annulation contrôlée et workflow de clôture existent (`PaymentService.php`, `CashJournal.php`).

### PERS-SEC-01 — Secrétaire / responsable des inscriptions

- **Pourquoi elle recommanderait** : saisir un lot sans apprendre des commandes ni reprendre la même famille trois fois.
- **Moments** : réinscriptions, demandes de place, délivrance d’attestation/carte.
- **Sans l’app** : [À CONFIRMER] feuilles Excel, dossiers papier ; mesurer doublons et temps par dossier.
- **Craintes** : erreur de frappe irréversible, import qui corrompt les dossiers, pression des parents.
- **KPI** : import de 2 000 lignes avec ≥ 98 % de lignes valides traitées en ≤ 1 jour (cible de test, non état actuel) ; taux de ressaisie < 2 %.
- **Preuve** : import élèves par commande ; certificats et cartes séparés.

### PERS-ENS-01 — Enseignant

- **Pourquoi il adopterait** : appel aussi rapide que la feuille papier, emploi du temps accessible, notes jamais perdues.
- **Moments** : premier cours, fin de composition, publication d’un relevé.
- **Sans l’app** : [À CONFIRMER] cahier d’appel, tableau de notes ; noter latence, erreurs et interruptions de cours.
- **Craintes** : surveillance punitive, jugement sur les compétences numériques, connexion qui efface la saisie.
- **KPI** : appel classe complète en ≤ 30 s ; 95 % séances validées avant fin de journée ; corrections documentées.
- **Preuve** : page d’appel et sync hors-ligne, saisie de notes affectée ; seuils à tester.

### PERS-PAR-01 — Parent / tuteur (bénéficiaire indirect, portail différé)

- **Pourquoi il recommanderait** : solde compréhensible, reçu consultable, absence/notes partagées sans déplacement.
- **Moments** : rentrée/frais, absence, bulletin.
- **Sans l’app** : [À CONFIRMER] appel, WhatsApp, déplacement ; nombre d’appels/semaine et temps perdu à mesurer.
- **Craintes** : numéro partagé donnant accès au mauvais enfant, données visibles par ex-conjoint/tuteur non autorisé, frais de data.
- **KPI** : réduction ≥ 30 % des demandes de solde au secrétariat durant pilote (cible à confirmer) ; aucun accès indu.
- **Décision** : portail explicitement reporté jusqu’à validation des workflows internes.

### PERS-SUR-01 — Surveillant général / vie scolaire

- **Pourquoi** : suivre absences, retards, incidents et sorties avec responsabilités claires.
- **Moments** : appel du matin, sortie, récidive.
- **Sans l’app** : [À CONFIRMER] cahier de présence et appels.
- **Craintes** : données excessives, usage disciplinaire sans contradictoire.
- **KPI** : délai de signalement d’une absence ; % suivis attribués/clos.
- **Statut** : rôle dédié non vérifié ; ne pas l’inclure dans une matrice ACL avant décision métier.

### PERS-ELE-01 — Élève (bénéficiaire, mineur)

- **Pourquoi** : accès simple à son emploi du temps et à ses résultats, seulement si l’école et le cadre légal le permettent.
- **Moments** : changement de salle/cours, résultat, convocation.
- **Craintes** : exposition publique, comparaison stigmatisante, compte pris par un tiers.
- **KPI** : consultation utile et incidents d’accès ; aucun « engagement » social à optimiser.
- **Décision** : pas de compte élève MVP ; analyser séparément l’intérêt et les garanties.

### PERS-INS-01 — Inspecteur / DRE (lecture de rapports)

- **Job** : recevoir des états exportables requis sans accès permanent aux données nominales.
- **Sans l’app** : [À CONFIRMER] formulaires/tableurs transmis selon exigences applicables.
- **Craintes** : format non conforme, divulgation d’identifiants de mineurs.
- **KPI** : temps de préparation d’un export validé ; taux de rejet du format.
- **Décision** : aucun format MEPST/DRE n’est figé ; recueillir une demande officielle avant développement.

## À valider lors du premier entretien

1. [À CONFIRMER] Les écoles pilotes sont-elles disponibles pour observation et test de bout en bout ?
2. [À CONFIRMER] Taille typique exacte, mix de niveaux et classes simultanées.
3. [À CONFIRMER] Quels irritants le personnel accepte-t-il de payer en temps ou en argent pour résoudre ?
4. [À CONFIRMER] Budget mensuel d’infrastructure actuellement nul ; aucun prix-cible n’a été indiqué pour l’abonnement par école.
