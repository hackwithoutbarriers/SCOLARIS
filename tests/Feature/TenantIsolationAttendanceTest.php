<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_read_or_mutate_another_school_attendance_resources(): void
    {
        [$schoolA, $schoolB] = [School::factory()->create(), School::factory()->create()];
        $yearB = AcademicYear::factory()->create(['school_id' => $schoolB->id]);
        $classB = ClassRoom::factory()->create(['school_id' => $schoolB->id, 'academic_year_id' => $yearB->id]);
        $teacherB = User::factory()->create(['school_id' => $schoolB->id, 'role' => 'teacher']);
        $teacherA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'teacher']);
        $subjectB = Subject::create(['school_id' => $schoolB->id, 'name' => 'Math']);
        TeacherAssignment::create([
            'school_id' => $schoolB->id, 'teacher_id' => $teacherB->id, 'class_room_id' => $classB->id,
            'subject_id' => $subjectB->id, 'academic_year_id' => $yearB->id,
        ]);

        $this->actingAs($teacherB);
        $session = $this->postJson('/api/attendance/sessions', [
            'class_room_id' => $classB->id, 'session_date' => now()->toDateString(),
        ])->assertCreated()->json();

        $this->actingAs($teacherA);
        $this->getJson('/api/attendance/sessions/'.$session['id'])->assertNotFound();
        $this->postJson('/api/attendance/sessions/'.$session['id'].'/sync', ['records' => []])->assertNotFound();
        $this->postJson('/api/attendance/sessions/'.$session['id'].'/validate')->assertNotFound();
        $this->getJson('/api/teacher/classes/'.$classB->id.'/students')->assertNotFound();
        $this->postJson('/api/attendance/sessions', [
            'class_room_id' => $classB->id, 'teacher_id' => $teacherB->id, 'session_date' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_forged_teacher_id_from_another_school_is_rejected_for_admin(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $yearA = AcademicYear::factory()->create(['school_id' => $schoolA->id]);
        $classA = ClassRoom::factory()->create(['school_id' => $schoolA->id, 'academic_year_id' => $yearA->id]);
        $directorA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'director']);
        $teacherB = User::factory()->create(['school_id' => $schoolB->id, 'role' => 'teacher']);

        $this->actingAs($directorA)->postJson('/api/attendance/sessions', [
            'class_room_id' => $classA->id, 'teacher_id' => $teacherB->id, 'session_date' => now()->toDateString(),
        ])->assertForbidden();
    }
}
