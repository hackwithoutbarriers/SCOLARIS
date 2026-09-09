# Gestion académique

Les données académiques sont tenant-aware (`school_id`) et utilisent les années, périodes, classes, matières et inscriptions existantes.

`SubjectConfig` versionne la configuration d'une matière par école, année et classe : coefficient (`weight`), méthode de moyenne, score maximal, seuil de réussite et règles JSON (bandes d'appréciation). `EvaluationRuleSet` versionne les règles générales d'un cycle/classe (système de notation, arrondi, méthode et règles). `Assessment` représente une évaluation et `Grade` une note liée à un élève et une évaluation.

Les notes sont validées contre le score maximal de l'évaluation. Le score normalisé est stocké avec six décimales afin d'éviter les arrondis intermédiaires. L'affichage peut arrondir à deux décimales.

L'import CSV transactionnel accepte actuellement `assessment_id`, `student_id` et `score`. Chaque ligne est contrôlée dans le tenant demandé et toute erreur annule l'import ; les erreurs sont retournées avec leur numéro de ligne.

Les modèles académiques sont audités à la création, modification et suppression. Les policies et le global scope d'école empêchent les accès inter-tenant.

Les présences validées sont injectées séparément dans `ReportCardData.attendance_summary`, filtrées par année scolaire et période. Elles ne modifient jamais les moyennes. Les templates contrôlent leur affichage avec `summary.show_attendance`.
