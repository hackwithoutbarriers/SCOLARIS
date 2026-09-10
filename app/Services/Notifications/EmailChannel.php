<?php

namespace App\Services\Notifications;

use App\Jobs\SendSchoolNotificationEmail;
use App\Models\Guardian;
use App\Models\NotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;

class EmailChannel implements NotificationChannelInterface, ShouldQueue
{
    public function __construct(private TemplateRenderer $renderer, private SchoolMailerService $mailer) {}

    public function send(Guardian $guardian, string $templateCode, array $variables): NotificationResult
    {
        $school = $guardian->school;
        if (! $school || ! $this->mailer->isConfigured($school) || ! filled($guardian->email)) {
            $log = NotificationLog::create([
                'school_id' => $guardian->school_id, 'student_id' => $this->studentId($guardian, $variables),
                'guardian_id' => $guardian->id, 'channel' => 'email', 'template_code' => $templateCode,
                'sent_by' => auth()->id(), 'status' => 'failed', 'payload' => $variables,
            ]);

            return new NotificationResult('email', 'failed', logId: $log->id, message: 'Email non configuré pour cette école ou ce responsable.');
        }
        $template = $this->renderer->template($guardian, $templateCode, 'email');
        $body = $this->renderer->render($template->body, $variables);
        $log = NotificationLog::create([
            'school_id' => $guardian->school_id,
            'student_id' => $this->studentId($guardian, $variables),
            'guardian_id' => $guardian->id,
            'channel' => 'email',
            'template_code' => $templateCode,
            'sent_by' => auth()->id(),
            'status' => 'queued',
            'payload' => $variables,
        ]);
        Bus::dispatch(new SendSchoolNotificationEmail($log->id, $school->id, $template->id, $guardian->email, $body));

        return new NotificationResult('email', 'queued', logId: $log->id, message: 'Email placé en file d’attente.');
    }

    private function studentId(Guardian $guardian, array $variables): int
    {
        return (int) ($variables['student_id'] ?? $guardian->students()->value('students.id'));
    }
}
