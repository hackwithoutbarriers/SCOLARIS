<?php

namespace App\Services;

use App\Models\ContactMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactMessageWhatsAppNotifier
{
    public function send(ContactMessage $message): void
    {
        $webhookUrl = config('services.whatsapp.webhook_url');

        if (! is_string($webhookUrl) || $webhookUrl === '') {
            return;
        }

        $payload = [
            'event' => 'contact_message.created',
            'recipient' => config('services.whatsapp.number'),
            'message' => [
                'id' => $message->id,
                'name' => $message->nom,
                'email' => $message->email,
                'phone' => $message->telephone,
                'subject' => $message->sujet,
                'content' => $message->message,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ];

        $request = Http::acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(10);

        $secret = config('services.whatsapp.webhook_secret');
        if (is_string($secret) && $secret !== '') {
            $request = $request->withHeaders(['X-Webhook-Secret' => $secret]);
        }

        try {
            $response = $request->post($webhookUrl, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('Le webhook WhatsApp est indisponible ; le message de contact a tout de même été enregistré.', [
                'contact_message_id' => $message->id,
                'webhook_host' => (string) parse_url($webhookUrl, PHP_URL_HOST),
                'exception' => $exception->getMessage(),
            ]);

            return;
        }

        if ($response->failed()) {
            Log::error('Le webhook WhatsApp a refusé le message de contact.', [
                'contact_message_id' => $message->id,
                'status' => $response->status(),
            ]);
        }
    }
}
