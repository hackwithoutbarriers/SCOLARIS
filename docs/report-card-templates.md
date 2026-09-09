# Templates de bulletins

`ReportCardData` est le contrat normalisé entre le moteur académique et le renderer. Il contient l'identité élève, la période, les matières, le résumé, l'appréciation et les présences ; il ne contient aucune logique de mise en page.

Un `ReportCardTemplate` appartient à une école et possède un numéro de version, un statut (`DRAFT`, `ACTIVE`, `ARCHIVED`), une orientation et une configuration JSON validée. La configuration documentée contient `page`, `header`, `student`, `subjects`, `summary`, `footer`, `sections` et `styles`. Elle permet d'activer/réordonner les sections, choisir les colonnes, les couleurs, la police, le logo, les textes et les signatures.

Le workflow d'un bulletin est `draft → review → approved → published`. Chaque transition crée une version immuable. Un bulletin publié ne doit pas être modifié silencieusement : il faut générer une nouvelle version.

`ReportCardRenderer` transforme le même `ReportCardData` selon le template choisi. Le moteur de calcul ne connaît pas le template. Le PDF serveur utilise Dompdf et prend en charge portrait/paysage, caractères UTF-8 et tableaux.

`summary.show_attendance` contrôle l'affichage du résumé `days_present`, `days_absent`, `days_late`, `days_excused` et `total_days`. Le calcul de ce résumé reste indépendant du moteur de notes.

Les templates et les bulletins sont filtrés par école. Les routes HTML/PDF utilisent le binding Eloquent tenant-aware : un identifiant forcé d'une autre école répond 404 et ne permet ni aperçu, ni PDF, ni génération.
