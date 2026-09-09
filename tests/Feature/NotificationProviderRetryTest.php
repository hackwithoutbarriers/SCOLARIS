<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\NotificationQueue;
use App\Models\School;
use App\Models\Student;
use App\Services\NotificationProvider;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationProviderRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_http_provider_retries_then_sends(): void
    {
        config([
            'attendance.notifications.provider' => 'http',
            'attendance.notifications.http_url' => 'https://sms.example.test/send',
            'attendance.notifications.max_attempts' => 3,
            'attendance.notifications.retry_delay_minutes' => 5,
        ]);
        app()->forgetInstance(NotificationProvider::class);
        Http::fakeSequence()->pushStatus(500)->pushStatus(200);

        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id]);
        $student->guardians()->attach($guardian, ['receives_sms' => true]);
        $notification = NotificationQueue::create([
            'school_id' => $school->id, 'student_id' => $student->id, 'guardian_id' => $guardian->id,
            'channel' => 'sms', 'provider' => 'http', 'recipient' => $guardian->phone,
            'event' => 'ABSENT', 'template' => 'absent', 'payload' => ['message' => 'test'],
            'status' => 'PENDING', 'scheduled_at' => now(), 'idempotency_key' => 'retry-test',
        ]);

        app(NotificationService::class)->dispatchPending();
        $notification->refresh();
        $this->assertSame('PENDING', $notification->status);
        $this->assertSame(1, $notification->attempts);

        $notification->update(['scheduled_at' => now()]);
        app(NotificationService::class)->dispatchPending();
        $this->assertDatabaseHas('notification_queue', [
            'id' => $notification->id, 'status' => 'SENT', 'attempts' => 2,
        ]);
        Http::assertSentCount(2);
    }
}
