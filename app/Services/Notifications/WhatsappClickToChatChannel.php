<?php

namespace App\Services\Notifications;

use App\Models\Guardian;
use App\Models\NotificationLog;
use App\Services\PhoneNumberFormatter;

class WhatsappClickToChatChannel implements NotificationChannelInterface
{
    public function __construct(private TemplateRenderer $renderer) {}

    public function send(Guardian $guardian, string $templateCode, array $variables): NotificationResult
    {
        $number = PhoneNumberFormatter::toE164((string) $guardian->phone);
        if (! $number) {
            $log = NotificationLog::create([
                'school_id' => $guardian->school_id, 'student_id' => $this->studentId($guardian, $variables),
                'guardian_id' => $guardian->id, 'channel' => 'whatsapp_link',
                'template_code' => $templateCode, 'sent_by' => auth()->id(),
                'status' => 'failed', 'payload' => $variables,
            ]);

            return new NotificationResult('whatsapp_link', 'failed', logId: $log->id, message: 'Numéro WhatsApp absent ou invalide.');
        }
        $template = $this->renderer->template($guardian, $templateCode, 'whatsapp');
        $message = $this->renderer->render($template->body, $variables);
        $log = NotificationLog::create([
            'school_id' => $guardian->school_id,
            'student_id' => $this->studentId($guardian, $variables),
            'guardian_id' => $guardian->id,
            'channel' => 'whatsapp_link',
            'template_code' => $templateCode,
            'sent_by' => auth()->id(),
            'status' => 'opened',
            'payload' => $variables,
        ]);

        return new NotificationResult(
            'whatsapp_link',
            'opened',
            "https://wa.me/{$number}?text=".urlencode($message),
            $log->id,
            'Lien WhatsApp généré; l’envoi doit être confirmé dans WhatsApp.',
        );
    }

    private function studentId(Guardian $guardian, array $variables): int
    {
        if (! empty($variables['student_id'])) {
            return (int) $variables['student_id'];
        }

        return (int) $guardian->students()->value('students.id');
    }
}
