<?php

namespace App\Services\Notifications;

use App\Models\Guardian;

class WhatsappTwilioChannel implements NotificationChannelInterface
{
    public function send(Guardian $guardian, string $templateCode, array $variables): NotificationResult
    {
        throw new \LogicException('Le canal WhatsApp Twilio est réservé à la phase 2.');
    }
}
