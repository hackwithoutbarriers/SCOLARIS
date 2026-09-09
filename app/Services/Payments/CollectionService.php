<?php

namespace App\Services\Payments;

use App\Models\CollectionReminder;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class CollectionService
{
    public function __construct(private NotificationService $notifications) {}

    public function schedule(?\DateTimeInterface $date = null): int
    {
        $date = $date ? \Carbon\Carbon::instance($date)->startOfDay() : today();
        $created = 0;
        Invoice::query()->whereIn('status', [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE])->chunkById(100, function ($invoices) use ($date, &$created): void {
            foreach ($invoices as $invoice) {
                $days = $date->diffInDays($invoice->due_date, false);
                $type = $days > 7 ? 'UPCOMING' : ($days >= 0 ? 'DUE' : (abs($days) >= 7 ? 'CRITICAL' : 'OVERDUE'));
                $period = $date->toDateString();
                $inserted = CollectionReminder::query()->insertOrIgnore([
                    'school_id' => $invoice->school_id, 'student_id' => $invoice->student_id, 'invoice_id' => $invoice->id,
                    'type' => $type, 'scheduled_period' => $period, 'channel' => 'sms', 'status' => 'PENDING',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($inserted > 0) {
                    $created += $inserted;
                    $this->notifications->queueFinancial($invoice->student, $invoice, $type === 'CRITICAL' ? 'payment_overdue' : 'payment_reminder', 'sms', [
                        'amount' => $invoice->total_amount,
                        'balance' => $invoice->balance(),
                        'due_date' => $invoice->due_date->toDateString(),
                    ]);
                }
            }
        });
        return $created;
    }
}
