<?php

namespace App\Services\Notifications;

use App\Models\Guardian;

class WhatsappChannelRouter implements NotificationChannelInterface
{
    public function send(Guardian $guardian, string $templateCode, array $variables): NotificationResult
    {
        $channel = match ($guardian->school?->whatsapp_provider ?? 'click_to_chat') {
            'twilio' => app(WhatsappTwilioChannel::class),
            'meta_cloud' => app(WhatsappMetaCloudChannel::class),
            default => app(WhatsappClickToChatChannel::class),
        };

        return $channel->send($guardian, $templateCode, $variables);
    }
}
