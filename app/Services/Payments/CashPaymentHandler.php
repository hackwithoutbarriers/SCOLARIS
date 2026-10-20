<?php

namespace App\Services\Payments;

use App\Models\Payment;

class CashPaymentHandler implements PaymentMethodHandler
{
    public function confirm(Payment $payment): Payment
    {
        return $payment;
    }
}
