<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectConfig;
use App\Models\Term;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_and_write_only_authorized_student_and_assessment(): void
    {
        [$school, $year, $term, $subject, $teacher, $class, $student] = $this->academicFixture();
        $otherStudent = Student::factory()->create(['school_id' => $school->id]);
        $config = SubjectConfig::create(['school_id' => $school->id, 'subject_id' => $subject->id, 'academic_year_id' => $year->id, 'grading_method' => 'weighted_average', 'weight' => 1, 'max_score' => 20]);
        $assessment = Assessment::create(['school_id' => $school->id, 'subject_config_id' => $config->id, 'term_id' => $term->id, 'teacher_id' => $teacher->id, 'title' => 'Devoir', 'max_score' => 20, 'weight' => 1]);
        $otherTeacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $otherAssessment = Assessment::create(['school_id' => $school->id, 'subject_config_id' => $config->id, 'term_id' => $term->id, 'teacher_id' => $otherTeacher->id, 'title' => 'Autre devoir', 'max_score' => 20, 'weight' => 1]);
        $this->actingAs($teacher);

        $this->getJson("/api/academic/students/{$student->id}/grades/{$term->id}")->assertOk();
        $this->getJson("/api/academic/students/{$otherStudent->id}/grades/{$term->id}")->assertForbidden();
        $this->postJson("/api/academic/assessments/{$assessment->id}/grades", ['student_id' => $student->id, 'score' => 15])->assertCreated();
        $this->postJson("/api/academic/assessments/{$otherAssessment->id}/grades", ['student_id' => $student->id, 'score' => 15])->assertForbidden();
        $this->postJson("/api/academic/assessments/{$assessment->id}/grades", ['student_id' => $otherStudent->id, 'score' => 15])->assertForbidden();
    }

    public function test_cross_school_and_accountant_cannot_read_or_write_academic_data(): void
    {
        [$school, $year, $term, $subject, $teacher, $class, $student] = $this->academicFixture();
        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);
        $config = SubjectConfig::create(['school_id' => $school->id, 'subject_id' => $subject->id, 'academic_year_id' => $year->id, 'grading_method' => 'weighted_average', 'weight' => 1, 'max_score' => 20]);
        $assessment = Assessment::create(['school_id' => $school->id, 'subject_config_id' => $config->id, 'term_id' => $term->id, 'teacher_id' => $teacher->id, 'title' => 'Devoir', 'max_score' => 20, 'weight' => 1]);
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']));

        $this->getJson("/api/academic/students/{$student->id}/grades/{$term->id}")->assertForbidden();
        $this->postJson("/api/academic/assessments/{$assessment->id}/grades", ['student_id' => $student->id, 'score' => 10])->assertForbidden();
        $this->getJson("/api/academic/students/{$otherStudent->id}/grades/{$term->id}")->assertNotFound();
    }

    public function test_report_card_requires_school_and_student_relationship(): void
    {
        [$school, $year, $term, $subject, $teacher, $class, $student] = $this->academicFixture();
        $card = app(\App\Services\ReportCardService::class)->generate($student, $year, $term);
        $this->actingAs($teacher);

        $this->get(route('report-cards.html', $card))->assertOk();
        $this->get(route('report-cards.pdf', $card))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $unrelated = Student::factory()->create(['school_id' => $school->id]);
        $otherCard = app(\App\Services\ReportCardService::class)->generate($unrelated, $year, $term);
        $this->get(route('report-cards.html', $otherCard))->assertForbidden();
        $this->get(route('report-cards.pdf', $otherCard))->assertForbidden();
    }

    public function test_director_delete_authorization_is_scoped_to_their_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);

        $this->assertTrue($director->can('delete', $student));
        $this->assertFalse($director->can('delete', $otherStudent));
    }

    private function academicFixture(): array
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'A', 'capacity' => 30]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Maths']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        Enrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'class_room_id' => $class->id, 'academic_year_id' => $year->id, 'enrollment_date' => '2026-09-01', 'status' => 'active']);
        TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'class_room_id' => $class->id, 'subject_id' => $subject->id, 'academic_year_id' => $year->id]);

        return [$school, $year, $term, $subject, $teacher, $class, $student];
    }
}
