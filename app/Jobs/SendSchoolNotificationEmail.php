<?php

namespace App\Jobs;

use App\Mail\SchoolNotificationMail;
use App\Models\MessageTemplate;
use App\Models\NotificationLog;
use App\Models\School;
use App\Services\Notifications\SchoolMailerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSchoolNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $logId,
        public readonly int $schoolId,
        public readonly int $templateId,
        public readonly string $recipient,
        public readonly string $body,
    ) {}

    public function handle(SchoolMailerService $mailer): void
    {
        $school = School::withoutGlobalScopes()->findOrFail($this->schoolId);
        $template = MessageTemplate::query()->findOrFail($this->templateId);
        $mailer->configureFor($school);

        try {
            Mail::mailer('dynamic')->to($this->recipient)->send(new SchoolNotificationMail($template, $this->body, $school->name));
            NotificationLog::withoutGlobalScopes()->whereKey($this->logId)->update(['status' => 'sent']);
        } catch (\Throwable $exception) {
            NotificationLog::withoutGlobalScopes()->whereKey($this->logId)->update(['status' => 'failed']);
            throw $exception;
        }
    }
}
