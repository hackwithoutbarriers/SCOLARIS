<?php
return ['notifications'=>[
    'provider'=>env('SMS_PROVIDER', env('ATTENDANCE_NOTIFICATION_PROVIDER','mock')),
    'http_url'=>env('SMS_API_URL'),
    'api_key'=>env('SMS_API_KEY'),
    'sender'=>env('SMS_SENDER'),
    'http_timeout'=>(int) env('SMS_TIMEOUT', 10),
    'late_threshold_minutes'=>(int) env('ATTENDANCE_LATE_THRESHOLD_MINUTES', 30),
    'absent_after_validation'=>(bool) env('ATTENDANCE_NOTIFY_ABSENT', true),
    'max_attempts'=>(int) env('ATTENDANCE_NOTIFICATION_MAX_ATTEMPTS', 3),
    'retry_delay_minutes'=>(int) env('ATTENDANCE_NOTIFICATION_RETRY_DELAY', 5),
]];
