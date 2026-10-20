<?php
namespace App\Services;
use App\Models\NotificationQueue;
class MockNotificationProvider implements NotificationProvider {
    public function send(NotificationQueue $notification): bool { return true; }
}
