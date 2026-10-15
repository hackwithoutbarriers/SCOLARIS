<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\NotificationQueue;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AbsenceFollowUpService;
use App\Services\AttendanceService;
use App\Services\NotificationPolicy;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
        app(NotificationService::class)->dispatchPending();
        $this->assertDatabaseHas('notification_queue', ['student_id' => $student->id, 'status' => 'SENT']);
        $record = AttendanceRecord::query()->where('student_id', $student->id)->firstOrFail();
        $this->assertDatabaseHas('absence_follow_ups', ['attendance_record_id' => $record->id, 'status' => 'TO_CONTACT']);
    }

    public function test_secretary_can_record_parent_contact_and_justification(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $student->enrollments()->create(['school_id' => $school->id, 'class_room_id' => $class->id, 'academic_year_id' => $year->id, 'enrollment_date' => now(), 'status' => 'active']);
        $this->actingAs($teacher);
        $session = AttendanceSession::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'class_room_id' => $class->id, 'teacher_id' => $teacher->id,
            'session_date' => today()->toDateString(), 'status' => 'OPEN',
        ]);
        app(AttendanceService::class)->sync($session, [['student_id' => $student->id, 'status' => 'ABSENT']]);
        app(AttendanceService::class)->validateSession($session, $teacher);
        $record = $session->records()->first();

        $this->actingAs($secretary);
        $this->assertTrue($secretary->isSecretary());
        $this->assertSame((int) $secretary->school_id, (int) $record->school_id);
        $followUp = app(AbsenceFollowUpService::class)->update($record, $secretary, 'JUSTIFIED', 'WhatsApp', 'Maladie signalée par le parent');
        $this->assertSame('JUSTIFIED', $followUp->status);
        $this->assertSame($secretary->id, $followUp->contacted_by);
    }

    public function test_censeur_can_manage_school_wide_attendance_and_follow_up(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $censeur = User::factory()->create(['school_id' => $school->id, 'role' => 'censeur']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $student->enrollments()->create([
            'school_id' => $school->id,
            'class_room_id' => $class->id,
            'academic_year_id' => $year->id,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($censeur);
        $this->getJson('/api/teacher/classes')->assertOk()->assertJsonFragment(['id' => $class->id]);
        $session = $this->postJson('/api/attendance/sessions', [
            'class_room_id' => $class->id,
            'teacher_id' => $teacher->id,
            'session_date' => today()->toDateString(),
        ])->assertCreated()->json();
        $this->postJson('/api/attendance/sessions/'.$session['id'].'/sync', [
            'records' => [['student_id' => $student->id, 'status' => 'ABSENT']],
        ])->assertOk();
        $this->postJson('/api/attendance/sessions/'.$session['id'].'/validate')->assertOk();
        $this->getJson('/api/attendance/history')->assertOk();

        $record = AttendanceRecord::query()->where('student_id', $student->id)->firstOrFail();
        $followUp = app(AbsenceFollowUpService::class)->update($record, $censeur, 'JUSTIFIED', 'Téléphone', 'Absence justifiée par la famille');
        $this->assertSame('JUSTIFIED', $followUp->status);
        $this->assertSame($censeur->id, $followUp->contacted_by);
    }

    public function test_teacher_attendance_class_access_requires_matching_assignment_year(): void
    {
        $school = School::factory()->create();
        $classYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2025-2026']);
        $assignmentYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026-2027']);
        $class = ClassRoom::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $classYear->id,
        ]);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathématiques']);
        TeacherAssignment::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'class_room_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $assignmentYear->id,
        ]);

        $this->actingAs($teacher)
            ->getJson('/api/teacher/classes')
            ->assertOk()
            ->assertJsonMissing(['id' => $class->id]);
        $this->getJson('/api/teacher/classes/'.$class->id.'/students')->assertForbidden();
    }

    public function test_justification_requires_a_meaningful_note(): void
    {
        $school = School::factory()->create();
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $session = AttendanceSession::factory()->create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'class_room_id' => $class->id, 'teacher_id' => $teacher->id,
        ]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $record = AttendanceRecord::create([
            'school_id' => $school->id, 'attendance_session_id' => $session->id,
            'student_id' => $student->id, 'status' => 'ABSENT', 'marked_at' => now(),
            'client_operation_id' => (string) str()->uuid(),
        ]);

        $this->expectException(ValidationException::class);
        app(AbsenceFollowUpService::class)->update($record, $secretary, 'JUSTIFIED', 'Email', 'ok');
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
