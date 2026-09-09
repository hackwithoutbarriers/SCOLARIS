<?php

return [
    'gateway' => env('PAYMENT_GATEWAY', 'manual'),
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
    'reminders' => [
        'days_before' => [7, 1, 0],
        'overdue_days' => [1, 7],
    ],
];
