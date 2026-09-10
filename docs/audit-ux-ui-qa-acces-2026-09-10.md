# Audit UX/UI, navigation et QA — SCOLARIS

**Date :** 10 septembre 2026  
**Périmètre :** interface Filament réellement enregistrée, pages métier, ressources, widgets, CSS chargé, policies et routes Laravel présentes dans le dépôt.

## Méthode et limites

Audit fondé sur :

- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Pages/`
- `app/Filament/Resources/`
- `app/Filament/Widgets/`
- `app/Policies/`
- `resources/css/filament-admin-premium.css`
- `resources/css/app.css`
- `routes/web.php`
- `php artisan route:list --except-vendor`
- tests Feature existants

Le résultat ci-dessous décrit le comportement branché dans le code. Les constats visuels sont issus de la structure CSS et Blade/Filament ; une validation manuelle avec comptes de chaque rôle et captures sur 390 px/1280 px reste nécessaire pour les détails de rendu dépendant des données.

---

## 1. Synthèse exécutive

| Axe | Niveau | Conclusion |
|---|---:|---|
| Direction visuelle | Bon | Palette premium cohérente, surcharge décorative à réduire |
| Responsive | À surveiller | Filament conserve ses breakpoints, mais les tableaux métier restent denses |
| Navigation | Risque élevé | La policy générique expose trop de menus de ressources école |
| Périmètre enseignant | Risque élevé | La saisie de notes doit filtrer avant affichage, pas seulement au moment d'écrire |
| Finance | Risque moyen/élevé | Le reversal existe côté service mais n'est pas exposé clairement dans l'UI |
| QA liens | Moyen | Les routes principales existent ; certaines navigations Livewire peuvent perdre l'audit |
| Parent | Hors périmètre actuel | Aucun rôle/portail parent n'est branché dans l'interface actuelle |

### Priorités immédiates

1. Restreindre la visibilité des ressources par rôle, pas uniquement l'autorisation d'écriture.
2. Filtrer les évaluations et élèves dans le périmètre enseignant avant affichage.
3. Exposer une action Filament « Corriger le paiement » utilisant le reversal.
4. Remplacer les liens `wire:click` + `href` de l'assistance super admin.
5. Ajouter des tests de navigation et de visibilité de menus par rôle.

---

## 2. Audit visuel et design comportemental

### 2.1 Ce qui fonctionne

- La surcharge premium est injectée séparément du CSS natif Filament dans [AdminPanelProvider.php](../app/Providers/Filament/AdminPanelProvider.php).
- Le thème utilise une palette lisible et cohérente :
  - bleu profond `#173B67` ;
  - bleu sombre `#102D50` ;
  - doré `#D6A84F` ;
  - vert `#168A68` ;
  - fond `#F4F7FB`.
- La hiérarchie des KPI est claire grâce aux cartes, à la typographie Manrope et aux valeurs accentuées.
- `prefers-reduced-motion` est traité.
- Le CSS premium ne redéfinit pas les grilles Filament ni les breakpoints principaux.
- Les pages métier ont une meilleure intention UX que les CRUD bruts :
  - centre de recouvrement ;
  - saisie rapide des notes ;
  - suivi des bulletins ;
  - assistance écoles.

### 2.2 Problèmes visuels

| ID | Constat visible | Impact | Correction exacte |
|---|---|---|---|
| UX-01 | Les polices DM Sans/Manrope sont chargées depuis Google Fonts dans [filament-admin-premium.css](../resources/css/filament-admin-premium.css) et [app.css](../resources/css/app.css). | Rendu variable hors connexion, flash de police et dépendance externe. | Embarquer les fichiers dans `resources/fonts/`, ajouter `@font-face` avec `font-display: swap`, garder une fallback système. |
| UX-02 | Ombres fortes répétées sur widgets, sections, tables et formulaires. | Aspect “cartes flottantes” excessif, moins institutionnel et plus lourd sur mobile. | Garder l'ombre forte pour dashboard/KPI ; remplacer les autres par bordure + ombre courte (`0 4px 16px`). |
| UX-03 | Motif de points dans `.fi-main-content::before` sur l'ensemble du panneau. | Distrait dans les tableaux, formulaires et finance. | Activer le décor uniquement pour dashboard/login/pages de synthèse ; désactiver sur imports, tables et caisse. |
| UX-04 | Les tableaux de supervision et de suivi imposent une largeur minimale ou plusieurs colonnes. | Défilement horizontal pénible à 320–390 px. | Rendre chaque ligne sous forme de carte mobile ; conserver le tableau à partir de `md`. |
| UX-05 | Plusieurs ressources exposent des labels Filament par défaut ou semi-techniques. | Perte de finition et compréhension plus lente. | Définir partout `navigationLabel`, `modelLabel`, `pluralModelLabel`, labels de colonnes et placeholders en français. |
| UX-06 | Les états vides ne sont pas uniformes entre pages Filament natives et pages métier. | L'utilisateur ne sait pas toujours quoi faire ensuite. | Composant d'état vide commun avec explication, importance, CTA autorisé et exemple de données. |
| UX-07 | Les filtres de contexte (école, année, période, classe) ne sont pas visuellement persistants dans tous les workflows. | Risque de travailler dans le mauvais contexte, surtout pour notes/présences. | Ajouter une barre de contexte persistante et un sélecteur de classe/période dans les pages enseignant. |

### 2.3 Formulaires

#### Points positifs

- [GradeEntry.php](../app/Filament/Pages/GradeEntry.php) utilise un repeater responsive (`1 colonne` puis `3 colonnes` à `md`).
- Les formulaires de finance utilisent une disposition en deux colonnes adaptée au desktop.
- Les confirmations existent déjà pour plusieurs actions métier.

#### Problèmes

- [AssessmentResource.php](../app/Filament/Resources/AssessmentResource.php) affiche `subjectConfig.id` au lieu d'un libellé matière/classe compréhensible.
- [EnrollmentResource.php](../app/Filament/Resources/EnrollmentResource.php) affiche la relation élève sur `first_name` uniquement.
- [GradeResource.php](../app/Filament/Resources/GradeResource.php) permet une édition inline de la note sans contexte visible de classe, période et enseignant.
- Les formulaires de création ne présentent pas partout un résumé de l'école, de l'année et de la période ciblées.

#### Corrections

```php
Select::make('subject_config_id')
    ->label('Matière et classe')
    ->getOptionLabelFromRecordUsing(
        fn (SubjectConfig $record): string =>
            "{$record->subject->name} — {$record->classRoom->name}"
    );
```

```php
Select::make('student_id')
    ->label('Élève')
    ->getOptionLabelFromRecordUsing(
        fn (Student $record): string =>
            "{$record->full_name} — {$record->student_number}"
    );
```

Ajouter une `Section` non éditable affichant :

- école ;
- année scolaire ;
- période ;
- classe ;
- matière ;
- rôle de l'utilisateur courant.

---

## 3. Matrice des rôles et menus

## 3.1 Matrice cible comparée au comportement actuel

| Domaine | Administrateur/Super admin | Directeur | Secrétaire/Comptable | Enseignant |
|---|---|---|---|---|
| Dashboard | SaaS multi-écoles | école complète | finance/inscriptions | classes et tâches personnelles |
| Écoles | Oui | Non | Non | Non |
| Utilisateurs | Oui | Selon décision métier, idéalement gestion limitée de son école | Non | Non |
| Abonnements SaaS | Oui | Non | Non | Non |
| Années/périodes/classes/matières | Oui | Oui | Lecture contrôlée si nécessaire | Lecture de son périmètre |
| Élèves/inscriptions/responsables | Oui | Oui | Oui si rôle secrétariat distinct | Lecture de ses classes |
| Évaluations | Oui | Oui | Non | Ses évaluations/matières |
| Notes | Oui | Approbation/contrôle | Non | Saisie de ses évaluations |
| Bulletins | Oui | Approbation/publication | Non | Vérification de ses classes |
| Présences | Oui | Supervision/correction | Non | Ses classes affectées |
| Paiements/caisse | Oui | Contrôle | Oui | Non |
| Dépenses | Oui | Contrôle | Oui selon policy | Non |

## 3.2 État actuel vérifié

### Super administrateur

Correctement séparé sur plusieurs surfaces :

- [SuperAdminOverview.php](../app/Filament/Widgets/SuperAdminOverview.php)
- [SchoolSupportOverview.php](../app/Filament/Widgets/SchoolSupportOverview.php)
- [SchoolSupport.php](../app/Filament/Pages/SchoolSupport.php)
- [SchoolSubscriptionResource.php](../app/Filament/Resources/SchoolSubscriptionResource.php)

Risque restant : les policies `before()` super admin contournent naturellement les policies métier. Toute action d'intervention doit donc être auditée et explicitement visible comme intervention cross-tenant.

### Directeur

Le directeur est le profil le plus proche de la matrice attendue. Il dispose des pages de configuration, scolarité, présences et finance.

Risque : `isDirector()` reconnaît plusieurs rôles techniques (`director`, `admin`, `principal`). Il faut documenter clairement si `admin` et `principal` sont des aliases historiques ou des rôles encore utilisables.

### Secrétaire/comptable

Le rôle réellement présent est `accountant`. Il bénéficie de `isFinanceOperator()`.

Risque : le système ne distingue pas secrétaire et comptable. Les inscriptions et responsables ne disposent pas d'un rôle dédié, alors que le besoin métier les sépare.

Correction :

- ajouter un rôle `secretary` si le métier le confirme ;
- donner à `secretary` les permissions élèves, responsables, inscriptions ;
- ne jamais lui donner les actions notes, bulletins, configuration académique ou reversal financier sans permission explicite.

### Enseignant

Le contrôle d'écriture dans [AcademicPolicy.php](../app/Policies/AcademicPolicy.php) est correctement orienté vers :

- l'école ;
- l'enseignant affecté à l'évaluation ;
- la classe de l'élève.

Mais la navigation et certaines listes sont trop larges :

- [GradeEntry.php](../app/Filament/Pages/GradeEntry.php) charge les évaluations sans filtre initial `teacher_id`;
- [GradeResource.php](../app/Filament/Resources/GradeResource.php) n'a pas de `canViewAny()` propre ;
- [AssessmentResource.php](../app/Filament/Resources/AssessmentResource.php) n'a pas de garde UI spécifique ;
- la policy générique permet `viewAny()` à tout utilisateur actif de l'école.

### Parent

Le parent n'est pas branché :

- aucun rôle `parent` dans `User` ;
- aucun compte lié à `Guardian` ;
- aucune page parent ;
- aucune policy parent ;
- aucune route parent.

Ce n'est pas un simple problème de menu : c'est une fonctionnalité absente du modèle d'authentification.

---

## 4. Risques de sécurité et de fraude

### P0 — Policy générique trop permissive

Dans [SchoolResourcePolicy.php](../app/Policies/SchoolResourcePolicy.php) :

```php
public function viewAny(User $user): bool
{
    return $user->is_active && $user->school_id !== null;
}
```

Cette policy est appliquée à de nombreux modèles dans [AppServiceProvider.php](../app/Providers/AppServiceProvider.php) :

- années scolaires ;
- classes ;
- inscriptions ;
- responsables ;
- matières ;
- périodes ;
- affectations ;
- configurations ;
- présences ;
- modèles de bulletins.

**Risque :** un comptable ou un enseignant peut voir un menu ou une liste qui ne devrait pas appartenir à son rôle, même si l'édition est ensuite refusée.

**Correction exacte :**

- créer des policies par domaine, ou au minimum des `canViewAny()` explicites par ressource ;
- ne jamais utiliser `viewAny = utilisateur actif de l'école` comme règle par défaut ;
- ajouter des tests HTTP/Filament vérifiant à la fois :
  - menu absent ;
  - accès URL refusé ;
  - requête de liste filtrée ;
  - action d'écriture refusée.

### P0 — Périmètre enseignant incomplet avant affichage

Dans [GradeEntry.php](../app/Filament/Pages/GradeEntry.php), le select d'évaluation doit être limité à :

```php
Assessment::query()->where('teacher_id', auth()->id())
```

Puis la liste des élèves doit être limitée par `teacherAssignments`.

La vérification `manageGrades` au moment de la sauvegarde est nécessaire mais insuffisante : une donnée visible est déjà une fuite de confidentialité.

### P1 — Reversal absent de l'interface finance

Le backend possède `PaymentService::reverse()`, mais [PaymentResource.php](../app/Filament/Resources/PaymentResource.php) n'expose pas une action métier claire.

**Correction exacte :**

```php
Tables\Actions\Action::make('reverse')
    ->label('Corriger le paiement')
    ->requiresConfirmation()
    ->form([
        Forms\Components\Textarea::make('reason')
            ->label('Motif')
            ->required()
            ->minLength(10),
    ])
    ->visible(fn (Payment $record): bool =>
        auth()->user()?->can('reverse', $record) === true
    )
    ->action(function (Payment $record, array $data): void {
        app(PaymentService::class)->reverse(
            $record,
            $record->amount,
            $data['reason'],
        );
    });
```

Le bouton doit être désactivé si le paiement est déjà entièrement reversé.

### P1 — Modification inline des notes trop risquée

`TextInputColumn::make('score')` dans [GradeResource.php](../app/Filament/Resources/GradeResource.php) donne une impression de modification instantanée.

**Correction :**

- retirer l'édition inline pour les notes validées/publiées ;
- afficher le statut de verrouillage ;
- exiger une action explicite « Modifier la note » ;
- journaliser l'ancienne et la nouvelle valeur ;
- interdire toute modification si la période est clôturée.

---

## 5. Liens, boutons et routes

### 5.1 Routes principales présentes

Les routes suivantes sont enregistrées et correspondent à des pages réelles :

- `/admin/grade-entry`
- `/admin/grade-import`
- `/admin/collection-center`
- `/admin/report-card-tracking`
- `/admin/report-cards`
- `/admin/school-support`
- `/admin/school-subscriptions`
- `/teacher/attendance`
- `/report-cards/{reportCard}/html`
- `/report-cards/{reportCard}/pdf`
- `/payments/{payment}/receipt`
- `/student-registry/csv`
- `/student-registry/pdf`
- `/students/{student}/card.pdf`
- `/classes/{classRoom}/cards.pdf`

### 5.2 QA-01 — Navigation et journalisation super admin

Dans [school-support.blade.php](../resources/views/filament/pages/school-support.blade.php), les liens utilisent simultanément `wire:click` et `href`.

```html
<a wire:click="recordIntervention(...)" href="...">
```

**Risque :** la navigation peut commencer avant la fin de la requête Livewire et l'audit peut ne pas être enregistré.

**Correction :**

- remplacer le lien par une méthode Livewire ;
- journaliser ;
- rediriger après succès via `$this->redirect(...)`.

### 5.3 QA-02 — Absence de retour rapide

Les pages suivantes doivent avoir un bouton « Retour au tableau de bord » :

- centre de recouvrement ;
- notes manquantes ;
- saisie rapide des notes ;
- suivi des bulletins ;
- assistance école ;
- journal de caisse ;
- import CSV.

Correction commune :

```php
protected function getHeaderActions(): array
{
    return [
        Action::make('dashboard')
            ->label('Retour au tableau de bord')
            ->icon('heroicon-o-arrow-left')
            ->url(Dashboard::getUrl()),
    ];
}
```

### 5.4 QA-03 — Pas de bascule de classe/période suffisamment rapide

L'enseignant doit pouvoir changer de :

- classe ;
- matière ;
- période ;
- évaluation ;

sans revenir au dashboard.

À ajouter sur les pages enseignant :

- sélecteur de classe persistant ;
- période active visible ;
- bouton « Réinitialiser les filtres » ;
- conservation des filtres dans l'URL lorsque Filament le permet.

### 5.5 QA-04 — Routes d'API non destinées à la navigation humaine

Les routes `/api/attendance/history`, `/api/payments/debtors` et `/api/payments/report` existent. Elles doivent rester des contrats JSON, pas des destinations de boutons UX.

Les liens du dashboard doivent pointer vers :

- worklists Filament ;
- centre de recouvrement ;
- pages de suivi ;

et non vers des endpoints JSON.

---

## 6. Fonctionnalités indispensables manquantes

| Fonctionnalité | État | Correction |
|---|---|---|
| Portail parent | Absent | Ajouter rôle, liaison `User ↔ Guardian`, dashboard et policies |
| Rôle secrétaire distinct | Absent | Ajouter rôle et permissions élèves/inscriptions |
| Retour dashboard uniforme | Partiel | Ajouter `getHeaderActions()` aux pages métier |
| Changement rapide de classe | Partiel | Ajouter contexte persistant enseignant |
| Reversal dans l'UI | Absent | Action Filament protégée sur paiements |
| Édition de note sécurisée | Trop directe | Retirer inline edit pour notes verrouillées |
| Filtrage enseignant pré-affichage | Incomplet | Filtrer évaluations, élèves et notes par affectation |
| Journal visible des corrections finance | Partiel | Afficher historique des reversals dans la page paiement |
| Tests de menus par rôle | Insuffisant | Ajouter tests de navigation Filament |
| Cartes mobiles pour tableaux | Partiel | Rendu mobile dédié sous `md` |

---

## 7. Matrice de tests QA recommandée

### Navigation

| Test | Attendu |
|---|---|
| Directeur ouvre configuration scolaire | HTTP 200 et menu visible |
| Comptable ouvre configuration scolaire | Menu absent ou 403 |
| Enseignant ouvre les notes | Seulement son périmètre |
| Enseignant sélectionne l'évaluation d'un autre professeur | Option absente |
| Comptable ouvre les notes | 403/menu absent |
| Directeur ouvre le centre de recouvrement | HTTP 200 |
| Enseignant ouvre le centre de recouvrement | 403/menu absent |
| Super admin ouvre les abonnements | HTTP 200 |
| Directeur ouvre les abonnements | 403 |

### Fraude métier

| Test | Attendu |
|---|---|
| Comptable modifie le statut d'un paiement | Refusé ou workflow contrôlé |
| Comptable utilise le reversal | Autorisé avec motif et audit |
| Comptable supprime un paiement | Refusé |
| Enseignant modifie une note d'une autre matière | Refusé |
| Enseignant modifie une note d'une autre classe | Refusé |
| Note d'une période clôturée | Refusée |
| Bulletin publié modifié directement | Refusé |
| Intervention super admin | Audit présent avant redirection |

### Responsive

À exécuter aux largeurs suivantes :

- 320 px ;
- 375 px ;
- 390 px ;
- 768 px ;
- 1280 px.

Pages prioritaires :

- login ;
- dashboard ;
- saisie des notes ;
- import CSV ;
- centre de recouvrement ;
- suivi des bulletins ;
- supervision des écoles ;
- abonnements.

---

## 8. Plan de correction exact

### P0 — Sécurité et périmètre

1. Ajouter `canViewAny()`/`canAccess()` explicite à chaque ressource métier.
2. Corriger [GradeEntry.php](../app/Filament/Pages/GradeEntry.php) pour filtrer les évaluations par enseignant.
3. Ajouter `getEloquentQuery()` filtré à [GradeResource.php](../app/Filament/Resources/GradeResource.php).
4. Ajouter les filtres enseignants dans [AssessmentResource.php](../app/Filament/Resources/AssessmentResource.php) et [MissingGrades.php](../app/Filament/Pages/MissingGrades.php).
5. Ajouter les tests `RoleNavigationVisibilityTest` et `TeacherGradeScopeTest`.

### P1 — Finance et navigation

1. Ajouter le reversal à [PaymentResource.php](../app/Filament/Resources/PaymentResource.php).
2. Corriger la redirection auditée de [SchoolSupport.php](../app/Filament/Pages/SchoolSupport.php).
3. Ajouter le retour dashboard sur les pages métier.
4. Retirer l'édition inline des notes verrouillées.

### P2 — Finition premium

1. Auto-héberger les polices.
2. Limiter les motifs décoratifs.
3. Réduire les ombres dans les tables et formulaires.
4. Créer le rendu mobile en cartes.
5. Ajouter une barre de contexte école/année/période/classe.

---

## Conclusion

La base visuelle est crédible et cohérente pour un SaaS premium. Le principal risque actuel n'est pas la couleur ou la typographie : c'est l'écart entre **ce que le rôle peut voir dans les menus**, **ce que les listes affichent** et **ce que la policy autorise finalement**.

La règle de qualité à appliquer partout est :

```text
Menu masqué
→ route protégée
→ query filtrée par tenant et rôle
→ action backend autorisée
→ audit de l'opération sensible
```

Tant que cette chaîne n'est pas homogène, l'interface peut sembler premium tout en restant trop permissive pour une gestion scolaire multi-écoles.
