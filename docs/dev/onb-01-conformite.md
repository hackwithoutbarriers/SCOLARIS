# IDEA-ONB-01 — Traitement des données et conformité

## Traitement mis en œuvre

- **Finalité** : à la demande de l’école, créer ou mettre à jour des dossiers d’élèves et leurs inscriptions dans l’année et la classe choisies.
- **Données utiles seulement** : nom, prénom(s), matricule éventuel, date de naissance et sexe facultatifs, nom du responsable, lien facultatif et téléphone normalisé.
- **Fichier** : CSV UTF-8, taille et nombre de lignes plafonnés. Il est placé sur le disque configuré pour le module, sous un nom aléatoire. Le disque doit être privé; aucun lien public n’est émis.
- **Staging** : les valeurs normalisées et les erreurs contenant la valeur à corriger restent dans `student_import_rows` pour l’aperçu, la reprise et le rapport. Ces données sont personnelles et doivent être protégées comme les dossiers métier.
- **Rapport** : export CSV autorisé seulement après contrôle d’accès au lot; les cellules à risque de formule sont neutralisées.
- **Journaux** : les événements du lot conservent l’acteur, l’école, le nom de fichier nettoyé, son empreinte, les compteurs et les horodatages. Les valeurs des lignes ne sont pas ajoutées aux journaux. Les journaux d’élèves/responsables filtrent les attributs personnels.
- **Transmission** : aucun service externe d’analyse, OCR, IA, SMS ou WhatsApp n’est appelé par le module.

## Conservation et suppression

- Fichier original, lignes de staging et rapports reconstitués : **30 jours par défaut**, puis purge planifiée, ou suppression du fichier et des lignes de staging lors d’une annulation avant traitement.
- Les imports terminés restent consultables jusqu’à échéance afin de produire le rapport; une suppression anticipée peut être réalisée par la commande de purge après vérification du lot.
- Les enregistrements métier créés ne sont pas supprimés par la purge ni par une annulation. Les événements d’audit et les sauvegardes peuvent conserver des métadonnées ou des données selon leurs politiques propres.
- Une copie temporaire locale d’un objet du disque partagé peut être créée pendant l’analyse; elle est dans le répertoire temporaire du système, avec permissions restreintes, et supprimée à la fin de la requête.
- Le disque, le cron et le worker doivent utiliser la même configuration et le même bucket/volume. En production Render, utiliser un disque partagé privé, par exemple S3, et non le disque local éphémère.

## Points à confirmer avec un juriste et l’IPDCP

1. Rôles respectifs de l’établissement et du fournisseur, base juridique et information des responsables d’élèves mineurs.
2. Finalité, durée de conservation applicable au fichier, au staging, aux rapports, aux journaux et aux sauvegardes; le choix technique de 30 jours n’est pas un avis juridique.
3. Procédure d’accès, rectification, contestation d’une correspondance et réponse à une demande de suppression.
4. Mesures de chiffrement, localisation du stockage, transferts hors du Togo, contrats de sous-traitance et gestion des incidents.
5. Application de la loi togolaise n° 2019-014 et exigences de l’IPDCP.

Aucune conclusion juridique n’est formulée. Avant le premier import de données réelles, compléter cette revue et tester la suppression sur le disque et les sauvegardes.
