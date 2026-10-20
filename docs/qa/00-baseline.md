# Phase 0 — Baseline UI

## Résumé de phase

1. Environnement local : PHP 8.5.10, Laravel 12.69.1, Filament 3.3.55, Livewire 3.8.8, Node 22.19.0, npm 10.9.3, Vite 7.3.6 et Tailwind CSS 4.3.3.
2. `composer install --no-dev --dry-run` et `npm ci --dry-run` valident les verrous sans modifier `vendor/` ni `node_modules/`; l'installation réelle à froid n'a pas été lancée.
3. `npm run build`, `php artisan filament:assets --no-interaction` et `php artisan optimize:clear` réussissent.
4. Le panneau expose 115 routes applicatives. La base de démo contient 20 écoles, 5 600 élèves, 140 classes, 305 matières, 60 périodes, 20 grilles tarifaires, 1 120 paiements et 2 280 affectations.
5. Chaque école de démo a une direction et un comptable; le seed contient 120 enseignants et un super-admin. Il n'existe aucun compte secrétaire ni dépense seedée.
6. Les pages d'accueil et de connexion répondent 200, sans erreur console, échec réseau ou débordement horizontal aux largeurs 360, 390, 768, 1280 et 1920 px.
7. Défaut reproduit : la police réellement appliquée à l'accueil et à la connexion est DM Sans, pas Roboto (UI-001); le test Playwright échoue sur cette assertion après avoir validé le réseau, les statuts et les débordements. Voir [l'écran de connexion avant correction](./captures/ui-001-login-before.png).
8. Le build de production produit 172 148 octets CSS/JS (41 510 octets gzip); le thème Filament inclut les sources Blade et PHP et le manifeste référence les trois entrées Vite.
9. Les 167 tests Laravel passent (1 087 assertions). Aucun navigateur Android réel n'a été testé.
10. Git n'est pas installé/disponible dans cet environnement et le dossier n'est pas un dépôt Git : branche `fix/ui-qa` et commits impossibles ici.

## Versions et pipeline observés

| Élément | Version / résultat |
| --- | --- |
| PHP / Composer | PHP 8.5.10 / Composer 2.10.3 |
| Laravel / Filament / Livewire | 12.69.1 / 3.3.55 / 3.8.8 |
| Alpine | Alpine fourni par Filament 3; aucune dépendance Alpine indépendante n'est déclarée dans `package.json` |
| Tailwind / Vite | Tailwind CSS 4.3.3 / Vite 7.3.6 |
| Node / npm | Node 22.19.0 / npm 10.9.3 |
| Build | `npm run build` réussit; CSS app 93 249 o, CSS Filament 27 383 o, JS 51 516 o |
| Publication Filament | `php artisan filament:assets --no-interaction` réussit; actifs publiés par la commande Laravel |
| Cache Laravel | `php artisan optimize:clear` réussit |
| Dépendances à froid | Composer `--no-dev --dry-run` annonce zéro installation/mise à jour et 35 suppressions de dépendances de développement. `npm ci --dry-run` réussit; aucune installation destructive n'a été exécutée. |

La feuille Filament utilise Tailwind v4 avec `source(none)` et déclare explicitement les vues Blade ainsi que `app/Filament/**/*.php`. La feuille publique scanne les vues Blade et JavaScript. Le build local avait bien accès à ces sources, mais le stage frontend du Dockerfile ne copiait pas `app/Filament` : la reproduction isolée a confirmé l'absence de `.opacity-60` et `.bg-gray-100` dans le CSS de production. Le correctif UI-002 et sa preuve sont consignés dans le catalogue et le diagnostic.

## Reproduction et métriques initiales

La capture Playwright vérifie `/` et `/admin/login` sur les cinq viewports indiqués, collecte erreurs JavaScript, requêtes échouées, réponses HTTP 4xx/5xx et débordements documentaires, puis vérifie la déclaration de Roboto et le chargement d'une vraie face. L'exécution avant correction échoue comme attendu pour DM Sans; elle passe après le correctif UI-001. Un second smoke authentifie les rôles secrétaire, directeur, comptable, enseignant et super-admin sur un tableau seedé, aux largeurs 390 et 1280 px.

```powershell
npm ci
npx playwright install chromium
npm run qa:ui
```

La commande prépare une base SQLite temporaire dans `%TEMP%` par `migrate:fresh --seed` et `DemoSchoolSeeder`, lance son propre serveur sur `127.0.0.1:8021` et stocke les sessions dans cette base. Deux écoles avec des données métier et tous les rôles (dont secrétaire) sont disponibles pour les smoke tests. La base est supprimée en fin d'exécution; les bases définies dans `.env` ne sont pas utilisées. Les captures de référence sélectionnées sont conservées dans `docs/qa/captures/`; les autres sorties et captures sont ignorées.

| Mesure de référence | Résultat |
| --- | ---: |
| Routes applicatives Laravel | 115 |
| Pages browser vérifiées | 2 pages publiques + tableau de bord pour 5 rôles; balayage des ressources/actions restant à faire |
| Viewports browser testés | 5 |
| Erreurs console et requêtes en échec | 0 sur ces deux pages |
| Débordements horizontaux | 0 sur ces deux pages |
| Police calculée | DM Sans (attendu : Roboto) |
| Bundle CSS + JS minifié | 172 148 o (41 510 o gzip) |
| Tests Laravel | 167 réussis, 1 087 assertions |

La différence entre le rendu Vite de développement et celui du build de production, l'accessibilité automatisée, les interactions authentifiées au-delà du tableau de bord et le balayage des routes restent à valider dans les phases suivantes. Le fichier préexistant `database/demo.sqlite` a été lu uniquement en agrégats; aucune donnée n'y a été modifiée. La commande UI utilise un seed temporaire autonome. Les captures ne contiennent aucune donnée d'élève ou d'utilisateur.
