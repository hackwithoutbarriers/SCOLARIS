<?php

namespace App\Services\Payments;

use App\Models\Payment;

/**
 * Keeps historical manual methods compatible until electronic providers are enabled.
 */
class LegacyPaymentMethodHandler implements PaymentMethodHandler
{
    public function confirm(Payment $payment): Payment
    {
        return $payment;
    }
}
