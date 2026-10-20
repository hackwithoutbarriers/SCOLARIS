# IDEA-ONB-01 — Cas de terrain synthétiques

Tous les fichiers de ce dossier sont des données inventées pour test. Les noms d’écoles, d’élèves et de responsables sont fictifs; les numéros sont des valeurs synthétiques et ne doivent jamais être utilisés pour contacter qui que ce soit. Aucun dossier réel n’a été copié.

## Fixtures

| Fichier | Scénario | Résultat attendu (avant comparaison à une base d’école) |
|---|---|---|
| `tests/Fixtures/onb01/01_cycles_complets.csv` | CSV UTF-8 avec BOM ajouté au fichier généré; séparateur `;`; deux lignes de titre avant l’en-tête; classes représentatives de la maternelle au lycée; accents, casse/espaces, dates variées, sexe, téléphones, colonnes parasites, ligne vide et total. | Acceptation de service testée : 28 lignes vues après l’en-tête, 20 prêtes, 4 avec avertissement, 5 en erreur, 2 doublons à vérifier et 1 total ignoré. Une ligne vide provoque des erreurs de champs requis; les catégories d’avertissement/doublon peuvent se chevaucher. La cellule avec deux numéros est bloquée, pas simplement avertie. |
| `tests/Fixtures/onb01/02_secondaire_windows1252.csv` | CSV Windows-1252, séparateur `,`, accents, date ISO, date à deux chiffres, date impossible, classe écrite autrement et responsables multiples dans une cellule. | 5 lignes; 3 champs entièrement valides, 1 date ambiguë à vérifier, 1 date impossible bloquante; aucune fusion sur les deux responsables. |
| `tests/Fixtures/onb01/03_entete_decale_et_conflits.csv` | UTF-8 sans BOM, séparateur `;`, deux lignes descriptives, en-têtes synonymes, matricule répété, personne exacte candidate, variation de classe, date en texte et date impossible. | Acceptation de service testée : 9 lignes vues, 4 prêtes, 3 avec avertissement, 3 en erreur et 2 doublons à vérifier; aucun footer ignoré. Une même ligne peut porter plusieurs avertissements ou erreurs. Les classes de cette fixture ne sont pas utilisées pour changer la classe explicitement choisie pour l’import. |
| `tests/Fixtures/onb01/04_multi_feuilles.xlsx` | Classeur synthétique avec feuilles maternelle et secondaire, cellule de titre fusionnée, en-têtes après lignes de titre, ligne vide, colonne parasite, dates Excel sérialisées, totaux et casse variée. | Fixture de terrain/documentaire pour une éventuelle V2. Le MVP fiche ne doit pas prétendre analyser XLSX; l’import doit le refuser clairement. Les feuilles contiennent 4 lignes élèves; 3 dates sérialisées valides et 1 cellule invalide à corriger. |

## Couverture métier synthétique

- Maternelle: petite, moyenne et grande section.
- Primaire: CI, CP1, CP2, CE1, CE2, CM1 et CM2.
- Collège: 6e, 5e, 4e et 3e avec suffixes/casse alternatifs.
- Lycée: seconde, première et terminale avec séries.
- Téléphones fictifs avec `+228`, `00228`, 8 chiffres, espaces, points, tirets, paire de numéros et format trop court.
- Noms fictifs en majuscules, minuscules, accents, variantes et espaces; sexes en `M`, `F`, `Masculin`, `féminin`.
- Matricules absents/non fournis, présents, répétés et différents pour la même personne; aucune fusion automatique n’est attendue.
- Dates ISO, `jj/mm/aaaa`, `jj-mm-aa`, texte, valeur impossible et numéro de série Excel en classeur XLSX.

## Règles d’acceptation à figer après pilote

1. Détecter l’en-tête parmi les lignes initiales et ignorer les notes/totaux sans écrire de dossier.
2. Refuser tout CSV non UTF-8 au MVP; le cas Windows-1252 sert à documenter l’écart et à décider d’une conversion contrôlée après validation terrain, pas à faire une conversion silencieuse.
3. Signaler les dates ambiguës ou impossibles; ne pas inventer un siècle pour les années à deux chiffres.
4. Ne pas choisir automatiquement entre deux téléphones ou deux responsables d’une cellule.
5. Comparer matricule et identité dans le périmètre de l’école seulement; nom ou téléphone seul ne déclenche jamais une fusion.
6. Montrer le numéro de ligne d’origine et une correction attendue; échapper les cellules de rapport commençant par `=`, `+`, `-` ou `@`.
7. Chaque import fonctionnel sélectionne une année/classe explicite et aucune donnée de la fixture ne peut être assignée hors école.

Les nombres ci-dessus sont des contrats synthétiques vérifiés par `StudentImportFixtureAcceptanceTest`, pas une mesure de données réelles ni une validation réglementaire. Ils devront être révisés uniquement après analyse d’un fichier pilote anonymisé.
