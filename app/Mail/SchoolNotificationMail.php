<?php

namespace App\Mail;

use App\Models\MessageTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SchoolNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MessageTemplate $template,
        public readonly string $body,
        public readonly string $schoolName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->template->subject ?: $this->schoolName);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.school-notification', with: ['body' => $this->body, 'schoolName' => $this->schoolName]);
    }
}
