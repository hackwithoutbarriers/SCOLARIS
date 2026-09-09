<?php

namespace App\Services;

use App\Models\NotificationQueue;

interface NotificationProviderInterface
{
    public function send(NotificationQueue $notification): bool;
}
