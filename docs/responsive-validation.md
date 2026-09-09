# Validation responsive Sprint 4

Le projet ne contient pas de runner navigateur (aucun Playwright, Dusk ou Cypress dans `package.json`). Une couverture serveur vérifie les pages Filament académiques et financières pour les largeurs 320, 375, 390, 768 et 1280 px dans `ResponsiveAcademicPagesTest`.

La vérification visuelle manuelle à effectuer dans DevTools est :

| Largeur | Pages | Vérifications |
| --- | --- | --- |
| 320 px | Saisie notes, import CSV, paiements, création paiement, structures de frais, dashboard | aucun défilement horizontal critique, montant et bouton d'action visibles |
| 375 px | mêmes pages | noms non coupés, clavier utilisable, formulaires empilés |
| 390 px | mêmes pages | actions principales accessibles au pouce, reçu disponible |
| 768 px | mêmes pages | grille et cartes respirent sans débordement, filtres utilisables |
| 1280 px | mêmes pages | tableau desktop complet, colonnes et actions lisibles |

La page de saisie utilise une colonne sur mobile et trois colonnes à partir de `md`; les blocs importants utilisent `rounded-xl`, Roboto et la palette Scolaris.

La liste des paiements conserve sur mobile l'élève, le montant et le statut comme informations principales; méthode, référence et date sont regroupées dans la description ou masquées par défaut. Les structures de frais suivent la même stratégie de colonnes essentielles.
