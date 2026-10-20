<?php

namespace App\Services\Notifications;

use App\Models\Guardian;

class WhatsappMetaCloudChannel implements NotificationChannelInterface
{
    public function send(Guardian $guardian, string $templateCode, array $variables): NotificationResult
    {
        throw new \LogicException('Le canal Meta Cloud est réservé à la phase 2.');
    }
}
