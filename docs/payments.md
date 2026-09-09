# Paiements

Le chemin manuel est `POST /api/payments` ou le formulaire Filament Payments. Les methodes MVP sont CASH, BANK, TMONEY, FLOOZ et OTHER. Le montant est valide comme entier positif; l'API exige un header `Idempotency-Key` pour empêcher un double envoi. Le recu est disponible sur `/payments/{id}/receipt`.

Une correction ne doit pas supprimer un paiement confirme. Elle doit utiliser une future operation de reversal/refund, afin de conserver la transaction et son audit. La reference saisie est unique par ecole.

Le dashboard expose l'attendu, l'encaisse, les impayes et le taux de recouvrement. Les debiteurs sont disponibles via `GET /api/payments/debtors`.
