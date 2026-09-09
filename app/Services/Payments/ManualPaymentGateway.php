<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;

class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function initiatePayment(PaymentTransaction $transaction): array
    {
        return ['status' => 'PENDING', 'merchant_reference' => $transaction->merchant_reference];
    }

    public function checkStatus(PaymentTransaction $transaction): array
    {
        return ['status' => $transaction->status, 'provider_transaction_id' => $transaction->provider_transaction_id];
    }

    public function handleWebhook(array $payload): array
    {
        return ['status' => 'PENDING', 'provider_transaction_id' => null, 'payload' => $payload];
    }
}
