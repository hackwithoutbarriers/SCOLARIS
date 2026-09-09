# Saisie et import des notes

## Saisie enseignant

La page Filament **Academic → Saisie rapide des notes** sélectionne une évaluation puis charge les élèves actifs de la classe concernée. Les champs sont présentés en une colonne sur petite largeur et en grille à partir de `md`, avec un champ de note directement éditable. L'enregistrement est groupé par bouton : aucune requête n'est envoyée à chaque frappe. La validation du score maximal est effectuée par `GradeCalculationService`.

La liste des notes conserve aussi une colonne `score` éditable inline sur desktop et tablette. Les actions sont accessibles dans la liste des notes via **Saisie rapide** et **Importer CSV**.

## Import CSV

Le format recommandé est disponible dans [examples/grades-import.csv](examples/grades-import.csv) :

```csv
student_number,subject,assessment,score,max_score
```

La page Filament **Importer des notes CSV** affiche les lignes analysées, importées, mises à jour, ignorées, doublons et erreurs ligne par ligne. Les identifiants historiques `assessment_id,student_id,score` restent acceptés par la commande/API.

L'import vérifie le tenant, l'élève, la matière, l'évaluation, la classe et le score maximal. Les notes existantes sont mises à jour et comptabilisées comme doublons ; aucune note inter-école ne peut être résolue.

## Responsive et permissions

Les cartes utilisent `rounded-xl`, la page est mobile-first et les répétiteurs passent d'une colonne mobile à trois colonnes à partir de `md`. Les policies et le scope école s'appliquent aux pages Filament, aux routes de bulletin et aux endpoints académiques.
