<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;

class FakePaymentGateway implements PaymentGatewayInterface
{
    public function initiatePayment(PaymentTransaction $transaction): array
    {
        return ['status' => 'PENDING', 'provider_transaction_id' => 'fake-'.$transaction->id, 'merchant_reference' => $transaction->merchant_reference];
    }

    public function checkStatus(PaymentTransaction $transaction): array
    {
        return ['status' => 'SUCCESS', 'provider_transaction_id' => $transaction->provider_transaction_id ?: 'fake-'.$transaction->id];
    }

    public function handleWebhook(array $payload): array
    {
        $status = strtoupper((string) ($payload['status'] ?? 'PENDING'));
        return ['status' => match ($status) {
            'SUCCESS', 'CONFIRMED' => 'CONFIRMED',
            'FAILED' => 'FAILED',
            'EXPIRED' => 'CANCELLED',
            default => 'PENDING',
        }, 'provider_transaction_id' => $payload['provider_transaction_id'] ?? null, 'payload' => $payload];
    }
}
