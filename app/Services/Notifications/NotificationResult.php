<?php

namespace App\Services\Notifications;

class NotificationResult
{
    public function __construct(
        public readonly string $channel,
        public readonly string $status,
        public readonly ?string $url = null,
        public readonly ?int $logId = null,
        public readonly ?string $message = null,
    ) {}
}
