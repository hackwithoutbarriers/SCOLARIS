# IDEA-ONB-01 — Cadrage et plan technique

> Document de cadrage initial conservé comme trace des exigences et écarts. Pour l’état courant, les mesures et les gates restants, utiliser `onb-01-rapport-mesure.md` et `onb-01-decisions.md`.

## Résumé de la fiche en 15 lignes

1. Le besoin prioritaire est de remplacer l’import élève par CLI par un parcours accessible au secrétariat.
2. Le MVP vise la première cohorte correctement inscrite en moins d’un jour ouvré.
3. Le KPI pilote est de traiter 2 000 élèves en moins d’un jour, avec au moins 98 % de lignes valides et aucun mauvais rattachement.
4. Le périmètre fiche est un import CSV UTF-8 d’élèves, responsables et inscriptions pour une année et une classe choisies.
5. La fiche demande un aperçu de 20 lignes et une validation complète avant toute écriture.
6. L’en-tête et les champs doivent être associés avant confirmation, avec modèle CSV téléchargeable.
7. Une ligne invalide reçoit un résultat explicite; elle ne fait pas échouer silencieusement les autres lignes.
8. Le traitement est idempotent, reprenable après coupure, et chaque ligne traitée une fois dans sa transaction.
9. Le rattachement impose l’école active, l’année et la classe de cette école; l’école est déterminée côté serveur.
10. Une collision de matricule ou un doublon potentiel est bloqué ou présenté en revue, jamais fusionné sans action humaine.
11. Les responsables peuvent être partagés entre élèves uniquement par lien explicite; le téléphone seul n’identifie pas une famille.
12. Le parcours secrétaire/directeur comprend fichier, mapping, année/classe, aperçu, confirmation et rapport d’erreurs.
13. Le rapport doit donner le numéro de ligne et les erreurs, et le fichier source n’est pas conservé par défaut.
14. La fiche reporte XLSX en V2 sauf bibliothèque déjà approuvée; OCR, finances, notes, fusion automatique et notifications parent sont exclus.
15. Les tests couvrent encodages, accents, 2 000 lignes, reprise après 400 lignes, doublons, rôles, isolation école et parcours E2E.

## Cartographie de l’existant réutilisable

| Besoin | État trouvé | Réutilisation / conséquence |
|---|---|---|
| Import CSV | `app/Services/StudentCsvImporter.php`, `app/Console/Commands/ImportStudentsCommand.php`; `league/csv` est déjà dans `composer.json`. Le service charge aujourd’hui le fichier entier, travaille en transaction globale et ignore l’inscription sans année. | Étendre le parcours métier avec analyse/aperçu puis lots reprenables; ne pas l’utiliser tel quel pour les exigences de flux/idempotence. Conserver le CLI compatible. |
| Élèves / matricules | `Student`, migration `2026_09_08_000007_create_students_table.php`; matricule unique par école. `Student::creating` s’appuie sur `ReceiptSequence` pour les numéros absents. | Garder le matricule source; laisser le modèle générer seulement les matricules absents. Une collision explicite est une erreur humaine à arbitrer, jamais une fusion ou un écrasement. |
| Responsables | `Guardian`, `guardian_student`, `Student::guardians()`, création manuelle multi-responsables dans `StudentResource`. | Relier explicitement les responsables; le téléphone ne constitue pas une clé de fusion familiale. Le MVP fiche garantit au minimum les champs responsables déjà pris en charge par le formulaire. |
| Inscription | `Enrollment`, garde de période et génération de facture via `InvoiceService` si grille active; unicité élève/année. | Créer l’inscription avec le service/modèle métier en transaction, après vérification année/classe/école. L’import ne doit pas contourner le déclenchement métier existant. |
| Onboarding | `app/Filament/Pages/SchoolOnboarding.php`, checklist dynamique de cinq étapes (école, année, classes, utilisateurs, élèves), persistée sur l’utilisateur. | Ajouter l’action import à l’étape élèves et mesurer la première inscription réussie; ne pas transformer cette fiche en projet d’activation plus large sans spécification dédiée. |
| ACL | `SecretaryResourcePolicy`, `StudentResource::canViewAny()`, rôles `director`/`secretary`, intervention super admin à durée limitée. `docs/audit/02-matrice-acl.md` prévoit directeur et secrétaire pour élèves/inscriptions, super admin seulement en intervention auditée. | Policy dédiée aux lots/lignes/rapports, contrôle de rôle et `school_id` à chaque accès, tests rôle × action et IDOR. Pas d’accès enseignant/comptable. |
| Audit | `AuditLog`, trait `Auditable`, service d’intervention super admin. Le trait enregistre les attributs des modèles. | Écrire un événement de lot avec métadonnées seulement (nom nettoyé, hash, acteur et compteurs), sans ligne source, nom d’élève ou téléphone. |
| Fichiers | Disque `local` privé et disque S3 déjà configuré; Livewire téléverse les temporaires sur le disque privé configuré. | `STUDENT_IMPORT_DISK` choisit le stockage des imports; il doit être privé/persistant et partagé entre Web Service et Cron Job en production. Les rapports sont téléchargés depuis la page autorisée, sans route publique de fichier. |
| Queue / scheduler | `config/queue.php` défaut `database`; `composer.json` propose `queue:listen` en environnement dev. `routes/console.php` a des tâches planifiées. | Le traitement de l’import est synchrone par lots reprenables; aucun worker n’est requis. Le cron lance la purge quotidienne. La disponibilité du worker de production demeure sans importance pour le MVP, mais le disque partagé est requis. |
| Tests | PHPUnit, `RefreshDatabase`, tests élèves/guardians, rôles, invitations et multi-tenancy présents. | Ajouter tests unitaires de normalisation/détection, intégration migration/transaction/reprise, ACL/IDOR et 2 000 lignes synthétiques. |

## Plan technique retenu à valider avec le code

1. **Données** : ajouter des tables de lots d’import et de résultats de lignes; ne pas recopier durablement les valeurs élèves brutes. Contraintes d’unicité `(school_id, batch_id, row_number)`, index de statut, hash fichier et stratégie idempotente.
2. **États** : suivre la fiche (`FichierChoisi → Apercu → Corriger → Confirme → EnCours → Termine/Partiel → reprise`). La variante simplifiée du prompt ne doit pas effacer l’état `Partiel` essentiel à la reprise.
3. **Analyse** : l’analyseur utilise `fgetcsv` en flux; imposer UTF-8, tolérer BOM, reconnaître les séparateurs configurés, identifier l’en-tête, proposer un mapping configurable avec confiance et aperçu borné à 20 lignes. Le CSV est le seul format fonctionnel MVP; aucune dépendance n’a été ajoutée.
4. **Normalisation** : services purs testés pour noms (trim, espaces, accents conservés), dates strictement reconnues/ambiguës signalées, sexe et téléphone. Ne pas inventer de règle réglementaire. Normaliser le téléphone seulement si compatible avec validation métier et la fiche; ne pas le prendre comme clé d’identité.
5. **Validation / doublons** : validation isolée par ligne; créer un manifeste d’erreurs lisible. Collision matricule exacte = ligne en revue/erreur bloquante sans mutation; ressemblance nominale = revue humaine. Toute mise à jour exige confirmation et garde d’école.
6. **Traitement** : service transactionnel par ligne ou lot borné, clé d’idempotence liée au lot et à la ligne, reprise des lignes non terminales. Limiter les lignes à 2 000 provisoirement et ne jamais tenir le fichier complet en mémoire.
7. **Métier** : créer Student, Guardian et Enrollment au moyen des modèles/services existants; faire valider l’interaction avec `InvoiceService` pour éviter facture involontaire ou doublée. L’année et classe sont requises.
8. **UX Filament** : assistant secrétaire/directeur sous « Élèves », modèle CSV, mapping/aperçu paginé, compteurs et confirmation explicite, progression de lot, résultats téléchargeables; aucun chargement XLSX dans l’écran MVP.
9. **Rapport / sécurité** : rapport CSV échappé contre injection de formules; téléchargement contrôlé par policy et école. Valider MIME/contenu, taille, extension, accès privé et nom aléatoire; ne pas journaliser les lignes.
10. **Activation** : relier le résultat réussi à l’étape Élèves déjà calculée par données réelles et à une date de première inscription/importation. Les autres actions (tarifs, messages, encaissement, présence, vue super admin de blocage) requièrent une fiche distincte ou une décision fonctionnelle et ne sont pas présumées incluses.
11. **Purges** : source et résultats de staging gardés 30 jours par défaut afin de permettre reprise et rapport, puis purge planifiée; cette durée est provisoire et doit être confirmée juridiquement.
12. **Migrations** : nouvelles migrations seulement, `down()` complet; jamais de réécriture de migration existante. Vérifier sur SQLite de test et PostgreSQL si la configuration/test disponible.

## Matrice ACL ciblée

| Action | Directeur | Secrétaire | Comptable | Enseignant | Super admin |
|---|---:|---:|---:|---:|---:|
| Créer/prévisualiser/confirmer/reprendre son école | Oui | Oui, selon matrice existante | Non | Non | Intervention temporaire et auditée uniquement |
| Voir/retélécharger rapport | Oui | Oui | Non | Non | Intervention temporaire et auditée uniquement |
| Accéder à lot/ligne/fichier d’une autre école | Non | Non | Non | Non | Non hors intervention ciblée |
| Fusionner identité ou déplacer un dossier | Non dans MVP | Non | Non | Non | Non |

## Livraisons mergeables proposées (sans commit dans cette copie locale)

- **P0** : cadrage, cartographie et décisions provisoires.
- **P1** : fixtures synthétiques, contrats d’acceptation et tests des formats prévus.
- **P2** : schéma, lecture CSV en flux, détection/mapping et normaliseurs purs.
- **P3** : validation, doublons, staging minimisé (valeurs normalisées nécessaires au rapport/reprise) et tests de reprise/idempotence.
- **P4** : intégration Student/Guardian/Enrollment et garde métier sans régression finance.
- **P5** : écrans Filament, ACL, rapports, stockage privé et purge.
- **P6** : activation bornée à l’onboarding actuel, tests E2E/performance, documentation et validation pilote.

Chaque livraison se termine par les tests ciblés verts et un rapport bref. Aucun commit n’est créé, conformément à la demande locale. Le répertoire de travail courant ne présente pas de métadonnées Git; la branche dédiée ne peut donc pas être créée ici.

## État de livraison dans cette copie locale

- P0 cadrage et décisions : documentés.
- P1 fixtures synthétiques : CSV/XLSX créés; acceptations de simulation validées pour les CSV UTF-8 pris en charge.
- P2–P5 moteur CSV, staging, mapping, normalisation, revue doublons, reprise, rapport, ACL et purge : implémentés et tests ciblés verts.
- P6 : tests ciblés et suite globale verte; performance mesurée pour analyse/simulation 2 000 lignes, pas pour 2 000 écritures métier ni PostgreSQL/S3.
- P7 : documentation technique, conformité et guide rédigés; validation sur fichier pilote anonymisé, mesures E2E avec utilisateurs et revue juridique encore requises.

## Écarts fiche ↔ prompt / questions résiduelles

- La fiche limite le MVP à CSV UTF-8 et reporte XLSX en V2; le prompt réclame des fixtures CSV et XLSX. Les fixtures XLSX peuvent servir de cas de terrain documentaire, mais leur exécution n’élargit pas le parseur MVP.
- La fiche préconise de ne pas conserver le fichier original par défaut et propose 90 jours pour les métadonnées/résultats à valider; le choix de travail applique 30 jours au fichier et au staging, à confirmer juridiquement.
- La fiche dit « résultats explicites par ligne », alors que le prompt demande d’abord staging brut/normalisé, mapping configurable, mise à jour proposée et annulation post-import. La copie durable des valeurs brutes et l’annulation réversible sont à exclure sauf arbitrage: la fiche ne les demande pas et signale l’annulation seulement si aucune dépendance irréversible.
- Le prompt demande une checklist d’activation étendue (tarifs, messages, encaissement, présence et super-admin); la fiche d’import constate une checklist existante mais ne spécifie pas ce tableau de bord. Limiter l’extension à l’import jusqu’à validation d’un périmètre d’activation dédié.
- Les classes de la fiche sont sélectionnées explicitement par import; la création automatique de classes et l’import simultané d’autres catégories ne sont pas MVP.
- Les réponses reçues: fixtures synthétiques uniquement; élèves/classes/responsables minimaux; conserver matricules et arbitrer collisions sans génération silencieuse; doublons exacts en proposition, approximatifs en revue, aucune fusion silencieuse; directeur/secrétaire selon ACL; cible 2 000 lignes; worker de prod inconnu; rétention fichiers 30 jours; capacité de revue non précisée.
- Restent à confirmer avec l’école pilote : variantes réelles des en-têtes/encodages et séparateurs, formats date et sexe, règles locales de téléphone, cas de doublons, génération des factures à l’inscription, capacité queue en production et réconciliation signée.
- Restent à valider par juriste/IPDCP : finalité, information des responsables, base de traitement, durées fichier/rapport/audit, droits de rectification, sauvegardes et transferts hors Togo.
