<?php
namespace App\Services;
use App\Models\NotificationQueue;
use App\Models\Student;
use App\Models\AttendanceSession;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class NotificationService {
    public function __construct(private NotificationPolicy $policy, private NotificationProvider $provider) {}
    public function queue(Student $student, string $event, array $payload = [], ?AttendanceSession $session = null): ?NotificationQueue {
        if (!$this->policy->shouldNotify($event, $student, $payload['late_minutes'] ?? null)) return null;
        $guardian = $student->guardians()->wherePivot('receives_sms', true)->where('guardians.active', true)->first();
        if (!$guardian) return null;
        $key = implode(':', [$student->school_id, $student->id, $session?->id ?? 'manual', $event]);
        $attributes = [
            'school_id'=>$student->school_id,'student_id'=>$student->id,'guardian_id'=>$guardian->id,
            'event'=>$event,'template'=>strtolower($event), 'recipient'=>$guardian->phone,
            'payload'=>json_encode($payload, JSON_THROW_ON_ERROR),'provider'=>config('attendance.notifications.provider','mock'),'channel'=>'sms',
            'status'=>'PENDING','scheduled_at'=>now(),
        ];
        NotificationQueue::insertOrIgnore(array_merge($attributes, [
            'idempotency_key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return NotificationQueue::where('idempotency_key', $key)->first();
    }

    public function queueFinancial(Student $student, Invoice $invoice, string $event, string $channel = 'sms', array $payload = []): ?NotificationQueue
    {
        $guardian = $student->guardians()->wherePivot('receives_sms', true)->where('guardians.active', true)->first();
        if (!$guardian) return null;
        $period = $event === 'payment_received' && isset($payload['payment_id'])
            ? 'payment-'.$payload['payment_id']
            : now()->toDateString();
        $key = implode(':', [$student->school_id, $student->id, $invoice->id, $event, $period, $channel]);
        NotificationQueue::insertOrIgnore([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'event' => $event,
            'template' => $event,
            'recipient' => $guardian->phone,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'provider' => config('attendance.notifications.provider', 'mock'),
            'channel' => $channel,
            'status' => 'PENDING',
            'scheduled_at' => now(),
            'idempotency_key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return NotificationQueue::where('idempotency_key', $key)->first();
    }

    public function queueForValidatedSession(AttendanceSession $session): void
    {
        $session->load('records.student');
        foreach ($session->records as $record) {
            $event = $this->policy->eventFor($record);
            if ($event) {
                $this->queue($record->student, $event, ['date' => $session->session_date->toDateString(), 'late_minutes' => $record->late_minutes], $session);
            }
        }
    }
    public function dispatchPending(): int {
        $sent = 0;
        for ($processed = 0; $processed < 100; $processed++) {
            $n = DB::transaction(function () {
                $notification = NotificationQueue::where('status', 'PENDING')
                    ->where(function ($query) {
                        $query->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $notification) {
                    return null;
                }

                $notification->update([
                    'status' => 'PROCESSING',
                    'attempts' => $notification->attempts + 1,
                ]);

                return $notification;
            });

            if (! $n) {
                break;
            }

            if ($this->provider->send($n)) {
                $n->update(['status'=>'SENT','sent_at'=>now()]);
                $sent++;
            } elseif ($n->attempts >= config('attendance.notifications.max_attempts', 3)) {
                $n->update(['status'=>'FAILED','failed_at'=>now(),'error'=>'Provider rejected message']);
                Log::warning('Notification provider permanently rejected message', ['notification_id' => $n->id]);
            } else {
                $n->update([
                    'status'=>'PENDING',
                    'scheduled_at'=>now()->addMinutes(config('attendance.notifications.retry_delay_minutes', 5)),
                    'error'=>'Provider rejected message',
                ]);
                Log::warning('Notification provider rejected message; retry scheduled', ['notification_id' => $n->id, 'attempts' => $n->attempts]);
            }
        }
        return $sent;
    }
}
