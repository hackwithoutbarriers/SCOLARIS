<?php

namespace App\Services;

use App\Mail\StaffInvitationMail;
use App\Models\NotificationQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationProvider implements NotificationProvider
{
    public function send(NotificationQueue $notification): bool
    {
        try {
            $payload = $notification->payload;
            $message = (string) ($payload['message'] ?? '');

            Mail::to((string) $notification->recipient)->send(new StaffInvitationMail(
                $message,
                (string) ($payload['invitation_url'] ?? ''),
            ));

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Email notification failed', [
                'notification_id' => $notification->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
