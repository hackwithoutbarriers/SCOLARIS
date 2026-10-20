<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $messageText,
        public readonly string $invitationUrl,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Invitation à rejoindre Scolaris')
            ->text('emails.staff-invitation');
    }
}
