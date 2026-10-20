# 03 — Catalogue d’idées de modules

## Résumé en 10 lignes

1. Les pistes sont évaluées pour les écoles moyennes à grandes, multi-niveaux ; elles ne sont pas des engagements de livraison.
2. Chaque idée a un persona et un KPI d’avant/après à établir sur pilote.
3. La priorité produit proposée est l’activation en autonomie, puis le recouvrement traçable et le pilotage.
4. Le paiement est en espèces pour le lancement ; la réconciliation mobile money reste préparatoire.
5. Le portail parent est reporté par décision du porteur, après validation des workflows internes.
6. La présence a déjà une synchronisation hors-ligne ; il faut la tester avant de généraliser un nouveau moteur offline.
7. Les alertes explicables peuvent être utiles, mais seulement après qualité des événements et règles validées.
8. La loi, les examens, les formats MEPST/DRE et les données de mineurs exigent une validation externe, pas une supposition produit.
9. Les idées d’IA, santé, comparaison inter-écoles et paiement payant sont écartées du MVP.
10. Le classement et les 8 fiches de spécification sont dans `04-priorisation-roadmap.md` et `05-fiches-modules/`.

## Critères de décision

- Les KPI ci-dessous sont des **cibles de pilote**, pas des performances actuelles.
- « Garder » signifie prototyper/valider ; « Transformer » signifie réduire ou recadrer ; « Reporter / rejeter » signifie ne pas construire maintenant.
- Une idée n’est retenue que si un utilisateur nommé l’utilise fréquemment, l’économie est mesurable, elle fonctionne avec Android/réseau modeste, son MVP est faisable seul sans API payante obligatoire et elle accumule une valeur défendable (règles, historique ou confiance).
- [À CONFIRMER] Chaque module doit être confronté aux pratiques de 3 à 5 écoles avant développement.

## D.1 Pilotage et alertes précoces

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-PIL-01 | Plusieurs pages séparées → tableau de bord direction horodaté (caisse, recouvrement, présence, résultats, retards). **Garder, MVP sans score**. | Directeur, propriétaire à distance | Minutes pour produire l’état → ≤5 min ; données avec date de fraîcheur = 100 %. |
| IDEA-PIL-02 | Les signaux d’élève sont dispersés → liste de suivi avec règles visibles et action suggérée. **Transformer** : seuils simples et consentement/accès contrôlés, après audit qualité. | Directeur, censeur | Cas à revoir détectés avant conseil → ≥90 % des cas validés par équipe ; faux positifs mesurés, pas d’objectif opaque. |
| IDEA-PIL-03 | Écarts de caisse ou opérations massives détectés tard → file d’anomalies explicables (montant, annulation, date, acteur). **Garder après journal d’événements**. | Comptable, directeur | Temps de détection/classement d’écart → <1 jour ; alertes utiles / alertes totales. |
| IDEA-PIL-04 | Échéanciers et encaissements peu lisibles à l’avance → projection déterministe basse/centrale/haute, sans prétendre prévoir avec certitude. **Transformer** après qualité des échéanciers. | Fondateur, comptable | Erreur absolue entre encaissement prévu et réalisé à 30 jours → réduire de 20 % vs baseline. |

## D.2 Recouvrement

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-REC-01 | Famille à plusieurs enfants, soldes éclatés → vue famille, responsables multiples, dettes par enfant et total. **Garder en fondation, sans compte parent**. | Comptable, secrétaire | Temps pour trouver solde complet → ≤30 s ; doublons famille <1 %. |
| IDEA-REC-02 | Promesse orale oubliée → échéancier négocié et promesse datée avec rappel à traiter. **Garder** (rappel interne gratuit, jamais auto-WhatsApp). | Comptable | Promesses échues sans suivi → réduire de 50 % en 6 semaines. |
| IDEA-REC-03 | Relances manuelles non uniformes → centre de messages avec modèles gradués, lien WhatsApp Click-to-Chat ou email configuré, statut « lien généré/ouvert », résultat saisi par humain. **Garder**. | Comptable, secrétaire | Familles relancées par heure → ×2 ; appels répétitifs au secrétariat −25 % ; aucun statut « livré » faux. |
| IDEA-REC-04 | Paiements partiels mal compris → affectation explicite aux échéances et solde recalculé. **Garder dans workflow existant**. | Comptable, parent par intermédiaire | Paiements non affectés à J+1 → <1 %. |
| IDEA-REC-05 | Reçu/attestation falsifié → référence signée/QR vers vérification minimale, sans afficher de données sensibles. **Transformer / différer** après analyse de menace. | Comptable, secrétaire, directeur | Documents contestés confirmés → 0 cas non résolu ; temps de vérification <30 s. |
| IDEA-REC-06 | Mobile money prévu plus tard → champ de référence et rapprochement manuel importable, sans API. **Garder en préparation** quand un besoin pilote l’exige. | Comptable | Transactions en suspens après rapprochement → <1 % à J+2. |

## D.3 Portail parents / élèves

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-PAR-01 | Appels pour solde/notes → PWA légère avec solde, reçus, absences, notes et bulletin. **Reporter explicitement** après workflows internes. | Parent | Appels/déplacements par semaine → −30 % sur pilote, sans hausse d’incident d’accès. |
| IDEA-PAR-02 | Numéros partagés et plusieurs tuteurs → accès par code remis en main propre, limité enfant/durée, révocable ; pas de SMS/OTP payant MVP. **Garder en recherche, pas en build**. | Parent, secrétariat | Tentatives d’accès indu / onboarding parent abandonné → 0 incident grave, ≥80 % accès autonome. |

## D.4 Moteur pédagogique et règles locales

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-PED-01 | Coefficients/barèmes varient selon niveau → règles versionnées par année/classe, aperçu avant application. **Garder** après validation d’écoles. | Censeur, directeur | Corrections de calcul post-publication → −80 % ; 100 % de bulletins reliés à une version de règle. |
| IDEA-PED-02 | Conseil reconstruit à la main → dossier classe imprimable/consultable (moyennes, absences, observations) et décisions consignées. **Garder**. | Censeur, enseignants | Préparation conseil par classe → −50 % de temps ; décisions non attribuées = 0. |
| IDEA-PED-03 | Examens exigent des listes → export générique paramétrable par colonnes. **Transformer** ; pas de format officiel codé avant source formelle. | Secrétaire, censeur | Temps de préparer la liste → −50 % ; rejets de format mesurés. |
| IDEA-PED-04 | Publication/correction tardive → versions visibles et recalcul avec piste d’audit. **Garder/étendre** le workflow existant. | Directeur, censeur | Correction non traçable → 0 ; délai correction→nouvelle publication mesuré. |

## D.5 Productivité des enseignants

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-ENS-01 | Appel long / réseau instable → mode exception (« tous présents sauf… »), local-first et synchronisation idempotente. **Garder et mesurer**, en s’appuyant sur la sync existante. | Enseignant | Temps par classe → médiane ≤30 s ; zéro perte après coupure simulée. |
| IDEA-ENS-02 | Notes saisies ligne à ligne → grille clavier, collage de cellules, validations et erreurs par ligne. **Garder**. | Enseignant | Temps de saisie 40 élèves → −50 % ; erreurs de valeur détectées avant sauvegarde ≥95 %. |
| IDEA-ENS-03 | Avancement pédagogique peu visible → cahier de textes/progression numérique. **Reporter** tant que appel/notes ne sont pas adoptés. | Enseignant, censeur | Progressions à jour à échéance → ≥90 % ; temps de saisie ≤2 min/cours. |
| IDEA-ENS-04 | Appréciations répétitives → suggestions déterministes éditables basées sur plages de notes/assiduité. **Transformer** ; pas d’IA générative MVP. | Enseignant | Temps par appréciation → −30 % ; 100 % relues et validées par enseignant. |
| IDEA-ENS-05 | Photo de feuille de notes → OCR de pré-saisie expérimentale, locale seulement. **Rejeter pour MVP** : fiabilité sur écriture locale, appareils et confidentialité non démontrées. | Enseignant | Précision cellule par cellule ≥99,5 % avant toute exploitation ; sinon pas de gain sûr. |

## D.6 Vie scolaire

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-VIE-01 | Incident et autorisation de sortie suivis sur papier → registre minimal, accès restreint, responsable, date/heure et preuve de remise. **À cadrer** avec direction/juriste avant données sensibles. | Censeur, surveillant | Délai incident→suivi <1 jour ; sorties sans preuve = 0 ; accès indu = 0. |
| IDEA-VIE-03 | Données de santé exposées → infirmerie numérique. **Ne pas faire sans cadrage juridique explicite et nécessité démontrée**. | Infirmier, direction | Pas de KPI de collecte tant que finalité/base légale/durée non validées. |

## D.7 Opérations de l’école

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-OPS-01 | Conflits de planning → validation de conflits avant publication, réemploi du calendrier existant. **Garder uniquement les frictions observées**. | Censeur | Conflits repérés après publication → −80 %. |
| IDEA-OPS-02 | Vacataires payés au forfait imprécis → relevé des heures planifiées/assurées et validation humaine de paie. **Reporter** après présence enseignant fiable. | Directeur, comptable | Écart heures validées/payées → <2 %. |
| IDEA-OPS-03 | Dépenses sans vue d’ensemble → budget simple par rubrique et dépenses approuvées. **Transformer** les dépenses/approbations déjà présentes. | Directeur, comptable | Dépenses sans rubrique/justificatif → <5 %. |
| IDEA-OPS-04 | Uniformes/manuels, cantine, transport ou bibliothèque suivis hors système → stock/registres simples et reçu, seulement si 3 pilotes confirment la douleur. **Reporter**. | Économe, secrétaire | Écart inventaire → <3 % ; temps d’inventaire −30 %. |
| IDEA-OPS-05 | Demande d’admission ressaisie → formulaire de pré-inscription transformé en inscription après décision. **Garder en option**, après import et consentement. | Secrétaire | Données ressaisies/dossier → zéro ; délai demande→décision mesuré. |
| IDEA-OPS-06 | Plusieurs campus sous même propriétaire → consolidation et comparaison par établissement. **Reporter** jusqu’à demande multi-site réelle ; tenant ≠ campus. | Fondateur de groupe | Temps de consolidation mensuelle → <15 min ; cloisonnement strict. |

## D.8 Robustesse et confiance

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-ROB-01 | Perte de réseau et conflits → file offline idempotente avec résolution visible. **Garder par verticales**, commencer présence et grille de notes. | Enseignant, comptable | Données perdues après coupure → 0 sur suite de tests ; conflits sans résolution >24h → 0. |
| IDEA-ONB-01 | Migration coûteuse → import Excel/CSV guidé, correspondance colonnes, aperçu, erreurs par ligne et création d’inscription. **Top priorité**. | Secrétaire, admin d’école | Temps import de 2 000 élèves → ≤1 journée ; lignes valides importées ≥98 %. |
| IDEA-DAT-01 | Crainte d’enfermement → export complet documenté, manifeste, état de sauvegarde fourni par l’hébergeur clairement indiqué, audit et test de restauration. **Garder comme fondation**. | Directeur, support | Export complet généré/vérifié en <1h ; restauration d’échantillon réussie à 100 %. |
| IDEA-ONB-02 | Déploiement obscur → checklist guidée, jeu fictif de formation, aide contextuelle. **Garder**, sans données d’élèves de démo en prod. | Directeur, secrétaire | Première valeur sur pilote → ≤1 jour ; tâches autonomes ≥80 %. |
| IDEA-INT-01 | API futures fragiles → interfaces internes de notification et paiement, contrat documenté ; aucune API publique large MVP. **Transformer** en ports internes minimaux. | Développeur / opérateur | Changer de canal sans changement du workflow métier ; tests de contrat verts. |

## D.9 Adoption et modèle

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-RET-01 | École silencieuse au démarrage → métriques d’activation minimisées côté SaaS et alerte support. **Garder**, agrégé et sans contenu d’élèves. | Support, fondateur produit | Écoles activées à J+7 → ≥80 % ; comptes inactifs contactés avant départ. |
| IDEA-BIZ-01 | Valeur peu lisible → abonnement par école socle, tester seulement ensuite options par module/élève et parrainage directeur ; coût total explicite. **Garder comme test commercial**. | Acheteur | Conversion essai→achat, rétention à 90 jours et parrainages convertis ; prix validé par acompte, pas sondage seul. |
| IDEA-SUP-01 | Support manque de contexte → lien WhatsApp vers support avec école/page/code erreur non sensible, opt-in utilisateur. **Garder**. | Personnel | Temps de diagnostic par ticket → −30 % ; zéro donnée élève dans le texte prérempli. |

## D.10 IA responsable

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-IA-01 | Écriture d’appréciation prend du temps → alternative déterministe basée sur plages explicites ; IA tierce facultative seulement après anonymisation, base juridique, coût et tests de biais. **Ne pas faire MVP**. | Enseignant | Gain ≥30 %, rejet des suggestions <10 %, aucune décision automatique ; coût/élève mesuré. |

## D.11 Données de valeur long terme

| ID | Problème → idée / décision | Utilisateur | KPI avant → cible après |
|---|---|---|---|
| IDEA-DATA-01 | Enquêter sur un incident exige des traces → journal structuré métier qui sert à audit/mesure. **Garder en fondation**, réduire les données de requête conservées. | Directeur, support | Événements critiques attribués à 100 % ; temps de reconstitution d’un paiement <5 min. |
| IDEA-DATA-02 | Comparaison inter-écoles séduisante mais sensible → statistiques agrégées seulement sur consentement et cadre légal validés. **Reporter**. | Fondateur / groupes | Aucune sortie avant seuil d’agrégation, consentement et revue juridique ; utilité mesurée par décision. |
