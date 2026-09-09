<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\ReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCardAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_card_attendance_summary_is_scoped_to_validated_term_and_year(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $this->actingAs($teacher);
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $otherYear = AcademicYear::create(['name' => '2025-2026', 'starts_at' => '2025-09-01', 'ends_at' => '2026-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $otherTerm = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 2', 'starts_at' => '2027-01-01', 'ends_at' => '2027-03-31', 'sort_order' => 2]);
        $class = ClassRoom::create(['academic_year_id' => $year->id, 'name' => '6e', 'grade_level' => '6']);
        $student = Student::factory()->create(['school_id' => $school->id]);

        foreach ([
            [$year, '2026-09-10', 'PRESENT'],
            [$year, '2026-09-11', 'ABSENT'],
            [$year, '2026-09-12', 'LATE'],
            [$year, '2026-09-13', 'EXCUSED'],
            [$year, '2027-01-10', 'ABSENT'],
            [$otherYear, '2026-09-14', 'ABSENT'],
        ] as [$sessionYear, $date, $status]) {
            $session = AttendanceSession::create(['academic_year_id' => $sessionYear->id, 'class_room_id' => $class->id, 'teacher_id' => $teacher->id, 'session_date' => $date, 'status' => 'VALIDATED', 'validated_at' => now()]);
            AttendanceRecord::create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => $status, 'marked_at' => now()]);
        }

        $summary = app(ReportCardService::class)->attendanceSummary($student, $year, $term);

        $this->assertSame(['days_present' => 1, 'days_absent' => 1, 'days_late' => 1, 'days_excused' => 1, 'total_days' => 4], $summary);
        $card = app(ReportCardService::class)->generate($student, $year, $term);
        $this->assertSame($summary, $card->normalizedData()->toArray()['attendance_summary']);
    }
}
