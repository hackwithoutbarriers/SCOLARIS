# 07 — Synthèse direction

## Résumé en 10 lignes

1. Scolaris a déjà un socle Laravel/Filament multi-école avec finance, présence, notes, bulletins et audit.
2. Le plus grand écart restant est l’activation validée sur le terrain : le MVP local d’import existe, mais n’a pas encore été éprouvé avec une école pilote.
3. La proposition de valeur doit commencer par une rentrée mieux maîtrisée, puis prouver le recouvrement.
4. Le directeur achète ; comptable, secrétaire et enseignant doivent gagner du temps au quotidien.
5. L’innovation défendable vient de règles locales traçables, de données fiables, d’une adoption rapide et de la reprise après coupure.
6. WhatsApp Click-to-Chat reste manuel ; aucun « envoi/livraison » ne doit être affirmé.
7. Le portail parent est reporté jusqu’à validation des parcours internes.
8. Le benchmark commercial n’est pas encore vérifié et ne justifie aucun comparatif public.
9. Budget d’infrastructure déclaré : 0 ; capacité hebdomadaire et prix d’abonnement à confirmer.
10. IDEA-ONB-01 a été choisi et implémenté localement; la validation pilote, juridique et de production reste le prochain gate.

## Vision produit — 3 phrases

Scolaris doit permettre à une école privée togolaise moyenne ou grande de mettre ses données en service rapidement, sans équipe technique ni dépendance à une API payante. Il doit rendre visibles les opérations qui protègent la trésorerie et le temps des personnels, avec une preuve compréhensible de qui a fait quoi et quand. Sa différenciation doit s’appuyer sur des règles pédagogiques explicables, une utilisation fiable sur réseau irrégulier et une confiance démontrée auprès de l’école.

## Les 5 paris recommandés

| Ordre | Pari | KPI pilote | Effort indicatif |
|---:|---|---|---:|
| 1 | Import guidé et inscription cohérente (IDEA-ONB-01) | 2 000 élèves en ≤1 jour, ≥98 % lignes valides | 8–14 j |
| 2 | Vue famille + promesses de paiement (IDEA-REC-01/02) | promesses échues sans suivi −50 % ; relances/heure ×2 | 14–24 j |
| 3 | Pilotage directeur et clôture explicable (IDEA-PIL-01) | état <5 min ; clôture ≤10 min ; 100 % écarts suivis | 5–9 j |
| 4 | Appel rapide et reprise offline (IDEA-ENS-01) | appel médian ≤30 s ; 0 perte pendant coupure simulée | 6–12 j |
| 5 | Règles versionnées + dossier de conseil (IDEA-PED-01/02) | corrections post-publication −80 % ; conseil −50 % temps | 22–36 j |

Ces efforts n’incluent pas l’attente de validation d’une école, d’une source officielle ou d’un juriste ; ils ne sont pas des dates. Capacité hebdomadaire inconnue, donc aucun engagement 30/90/180 calendrier.

## Ce qu’on ne fait pas maintenant

- Pas de portail parent avant workflows internes validés : risque de numéros partagés, droits multiples et support prématuré.
- Pas d’IA générative/OCR sur des données de mineurs : gain non démontré, coût/transfert et précision risqués ; privilégier règles déterministes.
- Pas de décision automatique de passage, sanction ou exclusion : l’école doit garder une décision humaine traçable.
- Pas d’intégration payante SMS/WhatsApp/mobile money avant volume et retour mesurés ; lancement espèces et Click-to-Chat manuel.
- Pas de données santé, comparaison inter-écoles ou export officiel supposé sans cadrage juridique/source compétente.
- Pas de stock/cantine/bibliothèque/multi-campus tant qu’une école pilote ne démontre pas une douleur prioritaire.

## Trois risques majeurs et parade

1. **Produit — l’import ou le tableau de bord ne répond pas aux données réelles** : vérifier les règles sur un fichier anonymisé, chronométrer le parcours et tester 2 000 inscriptions avant production.
2. **Données/réglementation — exposition des informations de mineurs ou accès parent indu** : minimisation, scoping, audit, absence de portail MVP ; revue par juriste/IPDCP avant nouveaux traitements.
3. **Exécution solo — trop de modules avant l’adoption** : un pilote, un KPI par module, ordre fondations → gains ; ne prendre qu’un pari à la fois et réutiliser les workflows existants.

## Gate actuel — IDEA-ONB-01

Le choix du premier pari est fait : **IDEA-ONB-01 — Import guidé et activation**. Son MVP CSV fonctionne localement avec des données synthétiques. Il reste à obtenir un fichier anonymisé autorisé, valider les règles métier et la conformité avec l’école pilote, mesurer le parcours complet et confirmer le déploiement privé/purge avant toute utilisation de données réelles.

Ne pas engager le module suivant ni annoncer le KPI pilote comme atteint avant cette validation. La capacité hebdomadaire, le partenaire pilote et la priorité suivante restent à définir.
