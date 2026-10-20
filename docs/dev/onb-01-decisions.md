# IDEA-ONB-01 — Décisions et défauts à valider

Les choix fonctionnels de référence suivent `docs/innovation/05-fiches-modules/IDEA-ONB-01-import-eleves.md`. Les décisions ci-dessous n’élargissent pas son MVP CSV.

| Sujet | Décision de travail | Statut |
|---|---|---|
| Données de pilote | Aucun fichier pilote fourni; développer et tester uniquement avec fixtures synthétiques couvrant les niveaux scolaires. | [DÉFAUT À VALIDER] Réviser sur fichier anonymisé avant de figer les règles. |
| Périmètre | Élèves, responsables minimaux et inscription dans une année/classe explicitement choisies. Pas de soldes, historique de notes, enseignants ou portail parent. | Confirmé par l’utilisateur et conforme à la fiche. |
| Matricules | Préserver les matricules sources; collision = revue/erreur bloquante à arbitrer, jamais écrasement ni fusion. Les absents peuvent utiliser le compteur atomique existant uniquement lorsque l’utilisateur confirme la création et les gardes modèle. | Politique confirmée; détails de génération à tester sur la base métier. |
| Doublons | Jamais de fusion silencieuse. Correspondance exacte proposée en mise à jour/revue; approximative placée à vérifier; création, mise à jour ou omission sont des décisions explicites. Le seul nom/téléphone ne suffit pas. | Politique confirmée; MVP de la fiche peut bloquer/placer en revue avant toute mutation. |
| Accès | Directeur et secrétaire si autorisés par la matrice; comptable et enseignant refusés; super admin seulement via intervention temporaire auditée. | Confirmé par l’utilisateur et la matrice ACL existante. |
| Volume | 2 000 lignes maximum provisoire par lot; traitement borné/reprenable, sans dépendre d’un worker. | [DÉFAUT À VALIDER] L’hébergement/queue de production reste inconnu. |
| Rétention du fichier | 30 jours maximum sur disque privé, puis purge planifiée; supprimer plus tôt si le rapport est terminé et aucune reprise n’est nécessaire. | [DÉFAUT À VALIDER] Choix utilisateur; validation juridique et politique de rapports/métadonnées encore requise. |
| Disque de production | Import staging configuré par `STUDENT_IMPORT_DISK`; le disque local est réservé au développement, et le Web Service/Cron Job doivent partager le même disque privé persistant. | [DÉFAUT À VALIDER] Choix S3 recommandé pour Render; tester les credentials, sauvegardes, purge et localisation. |
| Limitation de débit | 10 téléversements par utilisateur et par école sur une fenêtre glissante d’une heure; le rejeu idempotent du même fichier ne consomme pas de tentative. | [DÉFAUT À VALIDER] Limite provisoire configurable via `STUDENT_IMPORT_UPLOADS_PER_HOUR`. |
| Formats de date | Accepter seulement formats déterministes documentés; dates ambiguës/impossibles sont présentées comme erreur, sans inférence de siècle. | [DÉFAUT À VALIDER] À comparer à un échantillon anonymisé pilote. |
| Téléphones | Garder une valeur normalisée seulement si elle passe les règles existantes; pas de rapprochement/fusion fondé sur le téléphone. Aucun format réglementaire présumé. | [DÉFAUT À VALIDER] Préfixe par défaut et politique de numéros locaux à tester avec l’école pilote. |
| XLSX | Hors parser MVP selon la fiche (V2 si bibliothèque approuvée). Une fixture documentaire XLSX n’est pas une promesse de prise en charge en import. | Décision imposée par la fiche, malgré le souhait de couvrir XLSX en Phase 1. |
| Annulation | Annulation explicite avant traitement : supprime le fichier privé et le staging. Aucune annulation post-commit ni suppression en cascade des élèves importés; toute correction métier suit les workflows existants. | Décision de sécurité; la fiche ne permet une annulation après coup que sans dépendance irréversible. |
| Données d’audit | Conserver acteur, école, hash, nom de fichier nettoyé, dates et compteurs; jamais valeurs de ligne, identité ou coordonnées dans les journaux/statistiques. | Obligatoire par sécurité et minimisation. |
| Activation | Conserver la checklist existante à cinq étapes, calculée sur la présence de données, avec une prochaine action unique et un lien direct. Ne pas ajouter les parcours financiers, messages, présence ni vue globale super-admin sans cadrage propre. | Implémenté localement; ne mesure pas encore l’opérationnalité complète définie pour un pilote. |
| Capacité de revue | Non précisée; livraisons petites, chacune testée. | À confirmer avec l’utilisateur. |
| Développement / Git | Travail local sans commit demandé. Le workspace visible ne contient pas de dépôt Git, donc pas de branche dédiée ni commits possibles dans cette copie. | Constat d’environnement. |

## Points juridiques à valider

- Rôle de l’école et du fournisseur, base et finalité du traitement.
- Information des responsables et processus de rectification.
- Durées distinctes pour fichier source, rapports, résultats de staging et audit, y compris sauvegardes.
- Accès opérationnel en intervention super-admin, localisation et transfert de données.
- Conformité loi togolaise n° 2019-014 et exigences IPDCP. Aucune conclusion juridique n’est formulée ici.

## Validation terrain indispensable

Avant d’activer en production: obtenir un fichier anonymisé d’une école pilote couvrant les cycles et niveaux utilisés, valider mapping/en-têtes/dates/téléphones/classes avec le secrétariat, comparer un échantillon de lignes créées à la source, confirmer la génération d’une facture éventuelle, mesurer 2 000 lignes et faire signer la réconciliation du lot.
