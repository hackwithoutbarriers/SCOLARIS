<?php
return ['notifications'=>[
    'provider'=>env('ATTENDANCE_NOTIFICATION_PROVIDER','mock'),
    'default_channel'=>env('NOTIFICATIONS_DEFAULT_CHANNEL', 'whatsapp'),
    'whatsapp'=>[
        'templates'=>[
            'absence'=>env('WHATSAPP_TEMPLATE_ABSENCE'),
            'retard'=>env('WHATSAPP_TEMPLATE_RETARD'),
            'rappel_echeance'=>env('WHATSAPP_TEMPLATE_RAPPEL_ECHEANCE'),
            'payment_received'=>env('WHATSAPP_TEMPLATE_PAYMENT_RECEIVED'),
            'payment_reminder'=>env('WHATSAPP_TEMPLATE_PAYMENT_REMINDER'),
            'payment_overdue'=>env('WHATSAPP_TEMPLATE_PAYMENT_OVERDUE'),
        ],
    ],
    'http_url'=>env('SMS_API_URL'),
    'api_key'=>env('SMS_API_KEY'),
    'sender'=>env('SMS_SENDER'),
    'http_timeout'=>(int) env('SMS_TIMEOUT', 10),
    'late_threshold_minutes'=>(int) env('ATTENDANCE_LATE_THRESHOLD_MINUTES', 30),
    'absent_after_validation'=>(bool) env('ATTENDANCE_NOTIFY_ABSENT', true),
    'max_attempts'=>(int) env('ATTENDANCE_NOTIFICATION_MAX_ATTEMPTS', 3),
    'retry_delay_minutes'=>(int) env('ATTENDANCE_NOTIFICATION_RETRY_DELAY', 5),
]];
