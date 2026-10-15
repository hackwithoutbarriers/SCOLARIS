<?php

return [
    'name' => env('ADMIN_NAME', 'Administrateur TRENOU'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
    'force_password_reset' => (bool) env('ADMIN_FORCE_PASSWORD_RESET', false),
];
