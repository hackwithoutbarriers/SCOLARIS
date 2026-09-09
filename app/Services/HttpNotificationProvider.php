<?php

namespace App\Services;

use App\Models\NotificationQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpNotificationProvider implements NotificationProvider
{
    public function send(NotificationQueue $notification): bool
    {
        $url = config('attendance.notifications.http_url');
        if (! $url) {
            Log::error('SMS provider URL is not configured.');
            return false;
        }

        try {
            $response = Http::timeout((int) config('attendance.notifications.http_timeout', 10))
                ->withToken((string) config('attendance.notifications.api_key'))
                ->post($url, [
                    'to' => $notification->recipient,
                    'sender' => config('attendance.notifications.sender'),
                    'message' => $notification->payload['message'] ?? $notification->event,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('SMS provider request failed.', [
                'notification_id' => $notification->id,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }

        if (! $response->successful()) {
            Log::warning('SMS provider returned an error.', [
                'notification_id' => $notification->id,
                'status' => $response->status(),
            ]);
            return false;
        }

        return true;
    }
}
