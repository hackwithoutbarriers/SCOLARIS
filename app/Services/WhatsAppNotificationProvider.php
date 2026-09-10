<?php

namespace App\Services;

use App\Models\NotificationQueue;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class WhatsAppNotificationProvider implements NotificationProvider
{
    public function send(NotificationQueue $notification): bool
    {
        try {
            $sid = config('attendance.notifications.whatsapp.templates.'.$notification->event);
            if (! $sid) {
                Log::warning('WhatsApp template is not configured', ['event' => $notification->event]);
                return false;
            }
            $to = $this->normalize((string) $notification->recipient);
            $client = new Client(config('services.twilio.sid'), config('services.twilio.token'));
            $client->messages->create('whatsapp:'.$to, [
                'from' => config('services.twilio.whatsapp_from'),
                'contentSid' => $sid,
                'contentVariables' => json_encode($notification->payload, JSON_THROW_ON_ERROR),
                'statusCallback' => route('webhooks.twilio.whatsapp'),
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp notification failed', [
                'notification_id' => $notification->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function normalize(string $phone): string
    {
        $phone = preg_replace('/[\s().-]+/', '', $phone) ?? '';
        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        } elseif (str_starts_with($phone, '228')) {
            $phone = '+'.$phone;
        } elseif (str_starts_with($phone, '9') && strlen($phone) === 8) {
            $phone = '+228'.$phone;
        }
        if (! preg_match('/^\+228\d{8}$/', $phone)) {
            throw new \InvalidArgumentException('Numéro WhatsApp togolais invalide.');
        }

        return $phone;
    }
}
