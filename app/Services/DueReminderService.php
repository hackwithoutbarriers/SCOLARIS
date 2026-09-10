<?php

namespace App\Services;

use App\Models\CollectionReminder;
use App\Models\Invoice;
use App\Models\School;

final class DueReminderService
{
    public function __construct(private NotificationService $notifications) {}

    public function schedule(?\DateTimeInterface $date = null): int
    {
        $date = $date ? \Carbon\Carbon::instance($date)->startOfDay() : today();
        $created = 0;

        School::query()->where('due_reminders_enabled', true)->chunkById(100, function ($schools) use ($date, &$created): void {
            foreach ($schools as $school) {
                $dueDate = $date->copy()->addDays((int) $school->due_reminder_days);
                Invoice::query()->where('school_id', $school->id)
                    ->whereDate('due_date', $dueDate)
                    ->whereIn('status', [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE])
                    ->with('student')
                    ->chunkById(100, function ($invoices) use ($date, &$created): void {
                        foreach ($invoices as $invoice) {
                            if ($invoice->balance() <= 0) {
                                continue;
                            }
                            $inserted = CollectionReminder::query()->insertOrIgnore([
                                'school_id' => $invoice->school_id,
                                'student_id' => $invoice->student_id,
                                'invoice_id' => $invoice->id,
                                'type' => 'RAPPEL_ECHEANCE',
                                'scheduled_period' => $date->toDateString(),
                                'channel' => 'whatsapp',
                                'status' => 'PENDING',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            if ($inserted) {
                                $created += $inserted;
                                $this->notifications->queueFinancial($invoice->student, $invoice, 'rappel_echeance', 'whatsapp', [
                                    'student_name' => $invoice->student->full_name,
                                    'amount' => $invoice->total_amount,
                                    'balance' => $invoice->balance(),
                                    'due_date' => $invoice->due_date->toDateString(),
                                ]);
                            }
                        }
                    });
            }
        });

        return $created;
    }
}
