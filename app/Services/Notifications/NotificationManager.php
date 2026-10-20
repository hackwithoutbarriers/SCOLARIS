<?php

namespace App\Services\Notifications;

class NotificationManager
{
    public function whatsapp(): NotificationChannelInterface
    {
        return app(WhatsappChannelRouter::class);
    }

    public function email(): NotificationChannelInterface
    {
        return app(EmailChannel::class);
    }
}
