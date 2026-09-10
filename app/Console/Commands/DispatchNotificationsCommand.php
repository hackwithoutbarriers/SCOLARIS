<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class DispatchNotificationsCommand extends Command
{
    protected $signature = 'notifications:dispatch';

    protected $description = 'Dispatch pending email, WhatsApp and SMS notifications';

    public function handle(NotificationService $notifications): int
    {
        $sent = $notifications->dispatchPending();
        $this->info("Dispatched {$sent} notification(s).");

        return self::SUCCESS;
    }
}
