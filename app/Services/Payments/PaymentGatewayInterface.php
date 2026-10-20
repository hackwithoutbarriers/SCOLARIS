<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    public function initiatePayment(PaymentTransaction $transaction): array;
    public function checkStatus(PaymentTransaction $transaction): array;
    public function handleWebhook(array $payload): array;
}
