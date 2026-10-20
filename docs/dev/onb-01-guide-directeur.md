# Importer vos élèves en 10 minutes — guide à valider sur pilote

## Avant de commencer

Préparez un fichier **CSV UTF-8** (5 Mo maximum, 2 000 lignes au plus) avec une ligne d’en-tête; le bouton **Télécharger le modèle CSV** fournit un exemple fictif. Prévoyez au minimum le nom, le prénom, le nom et le téléphone d’un responsable. Les matricules existants sont conservés; laissez la cellule vide si l’école n’en utilise pas. Une année active et une classe de destination doivent déjà exister.

Les colonnes supplémentaires seront ignorées tant qu’elles ne sont pas associées. Le MVP ne lit pas les fichiers XLSX; enregistrez une copie CSV UTF-8 depuis votre tableur. Ne mettez pas plusieurs numéros dans la même cellule.

## Les étapes

1. Dans **Élèves → Importer des élèves**, choisissez votre CSV, l’année scolaire et la classe de destination, puis sélectionnez **Analyser le fichier**.
2. Vérifiez les colonnes proposées. Corrigez les menus déroulants si une colonne est mal reconnue; les colonnes inutiles restent sans correspondance.
3. Sélectionnez **Vérifier et simuler**. Aucune inscription n’est encore créée. Consultez les lignes prêtes, avertissements, erreurs et doublons; téléchargez le rapport pour corriger les valeurs concernées dans votre fichier.
4. Pour chaque doublon à vérifier, choisissez explicitement **Créer**, **Mettre à jour** ou **Ignorer**. Aucun dossier n’est fusionné automatiquement.
5. Lorsque l’impact affiché est compris, choisissez **Confirmer et traiter un lot**. Le traitement porte sur 100 lignes au plus par action. Si la page est interrompue, revenez au même lot et utilisez **Reprendre le traitement**.
6. Consultez les compteurs et téléchargez le rapport. Les lignes en erreur sont expliquées; elles ne bloquent pas les lignes valides.

## Reprendre après une interruption

Le lot actif est automatiquement rouvert lorsque vous revenez dans **Importer des élèves**. La checklist **Démarrage de l’école** pointe également vers ce lot lorsqu’un import doit être repris. La simulation et le mapping sauvegardés sont restaurés; les lignes déjà créées ou mises à jour ne sont pas rejouées.

## Préparer une démonstration ou un pilote

- **Démonstration** : utiliser exclusivement les CSV synthétiques de `tests/Fixtures/onb01/` (cycles complets ou en-têtes décalés). Créer une année et des classes de démonstration avant la session, puis montrer d’abord le modèle, la simulation, les corrections et le résultat.
- **Pilote** : attendre un fichier anonymisé fourni et autorisé par l’école. Vérifier une copie dans un environnement de test avec le directeur/secrétaire, valider les erreurs et doublons, puis convenir d’une réconciliation avant toute utilisation réelle.
- Ne jamais présenter le résultat de fixtures comme un taux de réussite terrain. Les règles métier, le rapprochement attendu et le temps de parcours restent à confirmer sur le fichier anonymisé.

## À retenir

- Directeur et secrétaire autorisés peuvent utiliser l’écran; comptable et enseignant n’y ont pas accès.
- Un import déjà commencé doit être terminé ou annulé avant d’en créer un autre pour la même école.
- L’annulation est possible avant toute inscription. Après création ou mise à jour d’élèves, l’import ne peut pas supprimer ces dossiers.
- Les fichiers et résultats de staging sont supprimés automatiquement après 30 jours par défaut. La durée doit être validée avant production.
- Les règles de dates, de téléphones, les en-têtes réels et le temps de préparation doivent encore être confirmés sur un fichier anonymisé d’école pilote. Ce guide ne promet pas encore une durée de réalisation de 10 minutes.

**Exemple fictif d’en-têtes** : `Matricule;Nom;Prénom;Date de naissance;Sexe;Classe;Tél parent;Responsable`.

*Captures d’écran à ajouter après le test pilote; ne pas capturer d’écran contenant des données personnelles d’élèves.*
