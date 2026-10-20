<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentMethodHandler
{
    public function confirm(Payment $payment): Payment;
}
