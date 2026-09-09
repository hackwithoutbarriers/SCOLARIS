# Payment providers

Le coeur ne depend d'aucun fournisseur. `PAYMENT_GATEWAY=manual` est le défaut; `fake` est disponible pour les tests. `PaymentGatewayInterface` definit `initiatePayment`, `checkStatus` et `handleWebhook`.

Les tentatives sont stockees dans `payment_transactions`; aucun secret ne doit etre place dans les payloads. Le webhook est `POST /api/payments/webhooks/{provider}` et exige une transaction interne existante, sa merchant reference et sa provider reference. En production, définir `PAYMENT_WEBHOOK_SECRET`; la signature HMAC-SHA256 doit être envoyée dans `X-Scolaris-Signature`. Le traitement est idempotent: une transaction deja CONFIRMED ne peut pas creer un second paiement.

Aucun credential fournisseur reel n'est present dans cet environnement. Une integration PayGate/FedaPay/CinetPay doit etre ajoutee comme adapter isole apres validation de leur documentation officielle et de leur signature webhook.
