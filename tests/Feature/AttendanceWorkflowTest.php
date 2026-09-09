<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\NotificationQueue;
use App\Services\NotificationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_validate_absence_and_queue_one_notification(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Math']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'class_room_id' => $class->id, 'subject_id' => $subject->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id]);
        $student->guardians()->attach($guardian, ['receives_sms' => true]);
        $student->enrollments()->create(['school_id' => $school->id, 'class_room_id' => $class->id, 'academic_year_id' => $year->id, 'enrollment_date' => now(), 'status' => 'active']);

        $this->actingAs($teacher);
        $session = $this->postJson('/api/attendance/sessions', ['class_room_id' => $class->id, 'session_date' => now()->toDateString()])->assertCreated()->json();
        $operationId = (string) str()->uuid();
        $payload = ['records' => [[
            'student_id' => $student->id, 'status' => 'ABSENT', 'client_operation_id' => $operationId,
        ]]];
        $this->postJson('/api/attendance/sessions/'.$session['id'].'/sync', $payload)->assertOk();
        $this->postJson('/api/attendance/sessions/'.$session['id'].'/sync', $payload)->assertOk();
        $this->assertDatabaseCount('attendance_records', 1);
        $this->postJson('/api/attendance/sessions/'.$session['id'].'/validate')->assertOk();
        $this->postJson('/api/attendance/sessions/'.$session['id'].'/validate')->assertOk();

        $this->assertDatabaseHas('notification_queue', ['student_id' => $student->id, 'status' => 'PENDING']);
        $this->assertSame(1, NotificationQueue::count());
        app(\App\Services\NotificationService::class)->dispatchPending();
        $this->assertDatabaseHas('notification_queue', ['student_id' => $student->id, 'status' => 'SENT']);
    }

    public function test_teacher_cannot_use_another_school_class(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $schoolB->id]);
        $class = ClassRoom::factory()->create(['school_id' => $schoolB->id, 'academic_year_id' => $year->id]);
        $teacher = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'teacher']);

        $this->actingAs($teacher)->postJson('/api/attendance/sessions', ['class_room_id' => $class->id, 'session_date' => now()->toDateString()])->assertForbidden();
    }

    public function test_director_only_can_view_dashboard_and_opted_out_guardian_is_skipped(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id]);
        $student->guardians()->attach($guardian, ['receives_sms' => false]);
        $student->enrollments()->create(['school_id' => $school->id, 'class_room_id' => $class->id, 'academic_year_id' => $year->id, 'enrollment_date' => now(), 'status' => 'active']);

        $this->actingAs($teacher)->getJson('/api/attendance/dashboard')->assertForbidden();
        $this->assertFalse(app(NotificationPolicy::class)->shouldNotify('LATE', $student, 5));
        $this->assertTrue(app(NotificationPolicy::class)->shouldNotify('LATE', $student, 45));
    }
}
