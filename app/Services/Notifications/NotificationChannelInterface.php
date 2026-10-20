<?php

namespace App\Services\Notifications;

use App\Models\Guardian;

interface NotificationChannelInterface
{
    public function send(Guardian $guardian, string $templateCode, array $variables): NotificationResult;
}
