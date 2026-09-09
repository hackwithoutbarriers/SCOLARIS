# Calcul des résultats

`GradeCalculationService` ne dépend pas de Filament ni du rendu PDF.

1. Chaque note est vérifiée dans l'intervalle `[0, max_score]`.
2. Elle est normalisée explicitement en pourcentage (`score / max_score * 100`).
3. Les évaluations d'une matière sont agrégées selon `SubjectConfig.grading_method` et leurs poids.
4. Les moyennes de matières sont pondérées par le coefficient de `SubjectConfig.weight`.
5. Les bandes d'appréciation sont triées par seuil décroissant et la première bande applicable est sélectionnée.

Les valeurs intermédiaires ne sont pas arrondies. L'arrondi appartient à la couche de présentation. `AppreciationService` calcule un classement avec ex aequo partageant le même rang (politique documentée et déterministe).

Les seuils, coefficients et méthodes sont des données versionnées de l'établissement : aucune règle MEPST n'est codée en dur.
