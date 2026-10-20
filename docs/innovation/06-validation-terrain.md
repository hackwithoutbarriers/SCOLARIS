# 06 — Kit de validation terrain

## Résumé en 10 lignes

1. Aucun module ne part en développement sur la seule base des hypothèses de ce dossier.
2. Le porteur indique qu’il existe des écoles pilotes, mais leurs profils et leur disponibilité restent à préciser.
3. Recruter 3 à 5 écoles avec directeur, comptable et enseignant ; inclure une école moyenne/grande et plusieurs niveaux.
4. Observer d’abord le travail actuel, sans montrer une solution ni demander une approbation générale.
5. Chronométrer les tâches sur données fictives ou anonymisées, jamais avec des mineurs comme sujets de test.
6. La baseline inclut papier, Excel, appels et WhatsApp réellement utilisés.
7. Mesurer l’adoption par rôle, pas seulement les connexions du directeur.
8. Le pilote dure 4 à 6 semaines et suit des indicateurs avant/après définis avec l’école.
9. Prévente par acompte ou engagement écrit, pas par « aimez-vous cette idée ? ».
10. Obtenir la validation juridique/IPDCP nécessaire avant tout traitement nouveau de mineurs ou transfert de données.

## 1. Recrutement et protocole

- **Échantillon** : 3–5 écoles privées togolaises, tailles et niveaux documentés ; chaque entretien avec directeur/fondateur, comptable, secrétaire et au moins un enseignant lorsque possible.
- **Durée** : 45–60 min d’entretien ; 30–45 min d’observation ; 30 min de test de tâches.
- **Consentement** : accord de l’adulte et de l’école, anonymiser notes ; aucune donnée nominative d’élève dans captures/exports d’étude.
- **Méthode** : question ouverte → « montrez-moi la dernière fois » → mesurer temps/erreurs/contournements → demander le coût réel → seulement après montrer un prototype.
- **Sortie** : baseline par école, fréquence, gravité, preuve observée, alternative actuelle, volonté de tester/payer. Séparer citation, observation et interprétation.

## 2. Guide d’entretien — 12 questions ouvertes

1. Racontez-moi la dernière rentrée : quelles tâches ont pris le plus de temps, qui les a faites et combien de temps ?
2. Montrez-moi comment vous inscrivez ou réinscrivez aujourd’hui une classe complète ; où les informations sont-elles ressaisies ?
3. Racontez le dernier encaissement contesté ou reçu à corriger ; comment avez-vous retrouvé le montant et la preuve ?
4. Comment une promesse de paiement est-elle notée, suivie et relancée aujourd’hui ? Qu’est-ce qui est oublié ?
5. Comment savez-vous à tout moment ce qui est réellement en caisse et quels écarts restent inexpliqués ?
6. Quand un parent demande le solde de plusieurs enfants, comment le personnel répond-il et combien de temps cela prend ?
7. Décrivez un appel de classe récent avec mauvais réseau ou téléphone indisponible ; qu’avez-vous fait pour ne perdre aucune présence ?
8. Racontez le dernier cycle de saisie de notes et préparation de bulletins ; quelles erreurs ont été corrigées après publication ?
9. Comment les règles de calcul ou de passage sont-elles décidées, conservées et expliquées lorsqu’un parent conteste ?
10. Quelles informations les parents demandent-ils le plus souvent, par quel canal, et quelle réponse doit rester humaine ?
11. Quels logiciels ou fichiers avez-vous déjà essayés, pourquoi les avez-vous gardés ou abandonnés, et combien coûtent-ils réellement en temps/argent ?
12. Si un seul résultat devait être garanti en 6 semaines, lequel vaudrait un achat ou une recommandation, et quel montant/engagement le prouverait ?

## 3. Hypothèses top 5 à invalider

| Pari | Hypothèse réfutable | Signal qui confirme | Signal qui réfute / arrêt |
|---|---|---|---|
| IDEA-ONB-01 Import | Les écoles ont des fichiers suffisamment cohérents et une secrétaire peut mener un import guidé seule. | ≥3 écoles fournissent fichier anonymisé ; ≥98 % lignes valides ; secrétaire termine 2 000 lignes simulées ≤1 j. | Formats trop différents ou >20 % exigent correction manuelle ; garder service d’import limité ou réduire périmètre. |
| IDEA-REC-01/02 famille + promesses | Un suivi famille/date réduit les promesses oubliées et accélère les relances. | ≥50 % baisse des promesses échues sans suivi en 6 semaines ; aucune fusion erronée. | Écoles n’enregistrent pas de promesses, ou définition du débiteur non consensuelle ; ne pas construire la fusion. |
| IDEA-PIL-01 Pilotage/caisse | Le directeur manque d’un état de caisse fiable et l’utilise chaque semaine. | ≥4 jours/semaine de consultation ou temps d’état <5 min, chiffres réconciliés avec journal. | Pas d’action déclenchée par les cartes, chiffres jugés incomplets ou aucun usage après 2 semaines. |
| IDEA-ENS-01 Appel offline | Réseau et vitesse font perdre l’appel, un flux d’exception améliore réellement la tâche. | Appel médian ≤30 s ; 0 perte sur 20 coupures ; 90 % enseignants choisissent le flux plutôt que papier. | Temps identique ou enseignants préfèrent le papier ; régler onboarding/terminal au lieu d’ajouter de la techno. |
| IDEA-PED-01/02 règles/conseil | Les règles actuelles peuvent être formalisées et la préparation du conseil est un coût élevé. | 3 écoles valident une règle commune par niveau ; correction bulletin −80 %, préparation −50 %. | Règles incompatibles ou décision dépend de pratiques non documentées ; garder seulement versionnement, sans automatiser. |

## 4. Test d’utilisabilité chronométré

Consigne : observer sans aider pendant 2 minutes ; noter départ/fin, clics, champs, demandes d’aide, hésitations, erreurs, retour au papier, réseau/appareil. Réussite = résultat exact, sans erreur critique, avec accès autorisé.

### Directeur / fondateur

1. Trouver le total encaissé aujourd’hui et sa date de fraîcheur : ≤60 s.
2. Identifier 5 comptes échus sans afficher de données inutiles : ≤2 min.
3. Examiner une clôture et demander une justification : ≤2 min.
4. Trouver le statut de saisie des notes d’une classe : ≤2 min.
5. Préparer une liste d’actions de rentrée : ≤5 min.

### Comptable / économe

1. Trouver un élève et confirmer la somme due : ≤30 s.
2. Enregistrer un paiement partiel en espèces et générer le reçu : ≤60 s.
3. Enregistrer une promesse datée : ≤60 s.
4. Préparer une relance WhatsApp et expliquer ce qui est confirmé : ≤45 s.
5. Soumettre clôture avec écart motivé : ≤3 min.

### Secrétaire

1. Mapper un fichier anonymisé : ≤5 min.
2. Corriger des lignes invalides à partir du rapport : ≤10 min.
3. Importer un lot test et créer les inscriptions : zéro mauvais rattachement.
4. Retrouver une famille et vérifier les responsables : ≤30 s.
5. Produire une attestation/carte : ≤2 min.

### Enseignant

1. Ouvrir sa classe affectée du jour : ≤10 s.
2. Faire appel en mode « tous présents sauf » : ≤30 s.
3. Perdre le réseau puis synchroniser : 0 donnée perdue.
4. Saisir/coller 40 notes : temps mesuré, cible −50 % vs fichier actuel.
5. Corriger une valeur hors barème sans publier : 0 erreur critique.

### Parent — uniquement prototype conceptuel, portail différé

1. Comprendre une échéance et un solde fictifs en ≤30 s.
2. Trouver le reçu d’un paiement simulé en ≤30 s.
3. Identifier l’enfant concerné si deux enfants partagent un numéro.
4. Comprendre qui peut accéder au compte et comment révoquer l’accès.
5. Trouver le contact école sans donner de données inutiles.

Critères transverses : aucune action inter-école possible, taux de réussite ≥80 % par persona avant extension, zéro erreur de montant/destinataire, interface lisible en extérieur sur Android bas de gamme. Réviser critères après baseline, sans abaisser la sécurité pour gagner du temps.

## 5. Pilote de 4 à 6 semaines

| Semaine | Mesures |
|---|---|
| 0 — baseline | Temps d’inscription/import, encaissement, relance, clôture, appel, saisie note ; erreurs, doublons et appels secrétariat ; top tâches papier. |
| 1 — activation | TTFV, lignes importées/échec, rôles invités, tâches d’aide/support ; objectif formation <2 h par rôle. |
| 2–3 — usage | Utilisateurs actifs par rôle/semaine, appels validés le jour même, promesses suivies, paiements rapprochés, liens de relance préparés/ouvertures manuelles. |
| 4–6 — résultat | Recouvrement à J+30, promesses tenues, temps caisse, temps de clôture, corrections notes, adoption des enseignants, demandes parent au secrétariat. |

### Indicateurs de succès à préenregistrer

- Recouvrement : montant collecté sur montant exigible par cohorte à J+30, méthode identique avant/après ; segmenter sans exposer individus.
- Caisse : durée de clôture et % écarts expliqués le jour même.
- Enseignants : % enseignants actifs et % appels validés dans la journée, pas seulement connexions.
- Adoption : secrétaire sait refaire import sans intervention ; directeur consulte les indicateurs et cite une action prise.
- Sécurité : accès inter-école = 0 ; export non autorisé = 0 ; incident de donnée mineur = 0.
- Qualité : aucun faux état « WhatsApp envoyé/livré » ; aucun paiement ou bulletin perdu en reprise.

Un résultat n’est attribué au module que si l’école garde la même définition de frais exigibles et si les facteurs externes (vacances, examens, remises) sont notés.

## 6. Prévente avant construction

1. Démonstration d’un prototype Figma ou storyboard avec données fictives ; ne pas importer un dossier réel dans un outil externe.
2. Présenter un engagement mesurable (ex. import 2 000 lignes en une journée, rapport de clôture lisible) et un prix par école testable.
3. Demander une lettre d’intention, un acompte remboursable selon conditions écrites, ou la réservation d’un créneau pilote avec responsable nommé.
4. Comparer prix annoncé avec alternatives réelles et coût actuel du temps ; ne pas confondre compliments et volonté de payer.
5. Arrêter/reformuler si moins de 2 écoles sur 5 acceptent une action concrète (fichier anonymisé, pilote, acompte ou temps dédié).

## 7. Critères de décision après pilote

- **Poursuivre** si l’indicateur principal s’améliore au seuil défini et qu’aucun risque d’accès/erreur critique n’apparaît.
- **Itérer** si intérêt confirmé mais le KPI manque à cause d’une friction UI identifiable et corrigible.
- **Arrêter** si usage dépend du développeur, si le personnel fait une double saisie persistante, si le consentement/autorisation de données n’est pas clair, ou si le gain n’est pas supérieur au coût d’exploitation.
