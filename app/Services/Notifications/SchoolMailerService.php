<?php

namespace App\Services\Notifications;

use App\Models\School;
use Illuminate\Support\Facades\Config;

class SchoolMailerService
{
    public function isConfigured(School $school): bool
    {
        return filled($school->mail_host)
            && filled($school->mail_port)
            && filled($school->mail_from_address);
    }

    public function configureFor(School $school): void
    {
        Config::set('mail.mailers.dynamic', [
            'transport' => 'smtp',
            'host' => $school->mail_host,
            'port' => $school->mail_port,
            'username' => $school->mail_username,
            'password' => $school->mail_password,
            'scheme' => $school->mail_encryption,
        ]);
        Config::set('mail.from', [
            'address' => $school->mail_from_address,
            'name' => $school->mail_from_name ?: $school->name,
        ]);
    }
}
