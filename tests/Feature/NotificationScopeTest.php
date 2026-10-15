<?php

namespace Tests\Feature;

use App\Http\Controllers\NotificationController;
use App\Jobs\SendSchoolNotificationEmail;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\NotificationLog;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Notifications\NotificationManager;
use App\Services\PhoneNumberFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_click_to_chat_uses_togolese_e164_and_logs_opened(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id, 'phone' => '90 00 00 00', 'email' => null]);
        $student->guardians()->attach($guardian->id, ['is_primary' => true]);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $session = AttendanceSession::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'class_room_id' => $class->id,
            'teacher_id' => $user->id,
            'session_date' => today()->toDateString(),
            'status' => 'VALIDATED',
        ]);
        $record = AttendanceRecord::create([
            'school_id' => $school->id,
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'ABSENT',
        ]);
        $this->actingAs($user);

        $result = app(NotificationManager::class)->whatsapp()->send($guardian, 'absence_notification', [
            'guardian_name' => $guardian->full_name, 'student_name' => $student->full_name,
            'class_name' => '6e A', 'date' => '10/09/2026', 'school_name' => $school->name,
            'student_id' => $student->id,
        ]);

        $this->assertSame('opened', $result->status);
        $this->assertStringStartsWith('https://wa.me/+22890000000?text=', $result->url);
        $this->assertDatabaseHas('notification_logs', ['channel' => 'whatsapp_link', 'status' => 'opened', 'student_id' => $student->id]);
        $logPayload = json_encode(NotificationLog::query()->firstOrFail()->payload);
        $this->assertStringNotContainsString($student->full_name, $logPayload);
        $this->assertStringNotContainsString($guardian->full_name, $logPayload);
        $this->assertSame('+22890000000', PhoneNumberFormatter::toE164('90 00 00 00'));

        $url = NotificationController::whatsappUrl($guardian, 'absence_notification', [
            'guardian_name' => $guardian->full_name, 'student_name' => $student->full_name,
            'class_name' => '6e A', 'date' => '10/09/2026', 'school_name' => $school->name,
            'student_id' => $student->id,
        ], ['student_id' => $student->id, 'attendance_record_id' => $record->id]);
        $query = (string) parse_url($url, PHP_URL_QUERY);
        $this->assertStringNotContainsString('variables', $query);
        $this->assertStringNotContainsString('student_id', $query);
        $this->assertStringNotContainsString($student->full_name, $url);
        $this->assertStringNotContainsString($guardian->full_name, $url);
        $redirect = $this->get($url)->assertRedirect();
        $this->assertStringContainsString('https://wa.me/', $redirect->headers->get('Location'));
        $this->assertSame(2, NotificationLog::count());
    }

    public function test_teacher_cannot_open_a_link_for_an_unrelated_student(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id, 'phone' => '90000000']);
        $student->guardians()->attach($guardian->id);
        $url = NotificationController::whatsappUrl($guardian, 'absence_notification', [
            'student_id' => $student->id, 'guardian_name' => $guardian->full_name,
            'student_name' => $student->full_name, 'class_name' => '6e A',
            'date' => '10/09/2026', 'school_name' => $school->name,
        ], ['student_id' => $student->id, 'attendance_record_id' => 999999]);

        $this->actingAs($teacher)->get($url)->assertForbidden();
        $this->assertSame(0, NotificationLog::count());
    }

    public function test_email_is_queued_when_school_smtp_is_configured(): void
    {
        Queue::fake();
        $school = School::factory()->create([
            'mail_host' => 'smtp.example.test', 'mail_port' => 587,
            'mail_from_address' => 'ecole@example.test', 'mail_from_name' => 'École',
        ]);
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id, 'email' => 'parent@example.test']);
        $student->guardians()->attach($guardian->id);
        $this->actingAs($user);

        $result = app(NotificationManager::class)->email()->send($guardian, 'report_card_ready', [
            'guardian_name' => $guardian->full_name, 'student_name' => $student->full_name,
            'report_card_url' => 'https://example.test/bulletin', 'school_name' => $school->name,
            'student_id' => $student->id,
        ]);

        $this->assertSame('queued', $result->status);
        $this->assertDatabaseHas('notification_logs', ['channel' => 'email', 'status' => 'queued']);
        Queue::assertPushed(SendSchoolNotificationEmail::class);
    }

    public function test_signed_email_link_does_not_expose_or_log_personal_variables(): void
    {
        Queue::fake();
        $school = School::factory()->create([
            'mail_host' => 'smtp.example.test', 'mail_port' => 587,
            'mail_from_address' => 'ecole@example.test', 'mail_from_name' => 'École',
        ]);
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create([
            'school_id' => $school->id,
            'phone' => '90000000',
            'email' => 'parent@example.test',
        ]);
        $student->guardians()->attach($guardian->id);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $session = AttendanceSession::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'class_room_id' => $class->id,
            'teacher_id' => $director->id,
            'session_date' => today()->toDateString(),
            'status' => 'VALIDATED',
        ]);
        $record = AttendanceRecord::create([
            'school_id' => $school->id,
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'ABSENT',
        ]);
        $url = NotificationController::emailUrl($guardian, 'absence_notification', [
            'guardian_name' => $guardian->full_name,
            'student_name' => $student->full_name,
            'school_name' => $school->name,
        ], ['student_id' => $student->id, 'attendance_record_id' => $record->id]);

        $this->assertStringNotContainsString($guardian->full_name, $url);
        $this->assertStringNotContainsString($student->full_name, $url);
        $this->assertStringNotContainsString('student_id', (string) parse_url($url, PHP_URL_QUERY));

        $this->actingAs($director)->get($url)->assertRedirect();
        Queue::assertPushed(SendSchoolNotificationEmail::class);
        $payload = json_encode(NotificationLog::query()->firstOrFail()->payload);
        $this->assertStringNotContainsString($guardian->full_name, $payload);
        $this->assertStringNotContainsString($student->full_name, $payload);
    }
}
