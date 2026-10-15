<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectConfig;
use App\Models\Term;
use App\Models\User;
use App\Services\GradeCalculationService;
use App\Filament\Resources\GradeResource\Pages\ListGrades;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use App\Filament\Pages\GradeEntry;
use Tests\TestCase;

class AcademicGradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_weighted_grades_are_normalized_and_calculated_without_filament(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id]));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $subject = Subject::create(['name' => 'Mathematics']);
        $config = SubjectConfig::create(['subject_id' => $subject->id, 'academic_year_id' => $year->id, 'grading_method' => 'weighted_average', 'passing_score' => 50, 'max_score' => 100, 'weight' => 100, 'rules' => ['grade_bands' => [['min' => 80, 'letter' => 'A'], ['min' => 50, 'letter' => 'C']]]]);
        $assessment = Assessment::create(['subject_config_id' => $config->id, 'term_id' => $term->id, 'title' => 'Quiz', 'max_score' => 20, 'weight' => 100, 'status' => 'published']);
        $student = Student::factory()->create(['school_id' => $school->id]);

        $service = app(GradeCalculationService::class);
        $service->saveGrade($assessment, $student, 16);
        $result = $service->calculate($student, $term);

        $this->assertSame(80.0, $result['summary']['average']);
        $this->assertSame('A', $result['subjects'][0]['grade_letter']);
    }

    public function test_score_above_assessment_maximum_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        \App\Models\Grade::validateScore(21, 20);
    }

    public function test_weighted_subjects_use_explicit_coefficients_without_premature_rounding(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id]));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $maths = Subject::create(['name' => 'Maths']);
        $french = Subject::create(['name' => 'Français']);
        $mathsConfig = SubjectConfig::create(['subject_id' => $maths->id, 'academic_year_id' => $year->id, 'grading_method' => 'weighted_average', 'weight' => 4, 'max_score' => 20]);
        $frenchConfig = SubjectConfig::create(['subject_id' => $french->id, 'academic_year_id' => $year->id, 'grading_method' => 'weighted_average', 'weight' => 3, 'max_score' => 20]);
        $mathsAssessment = Assessment::create(['subject_config_id' => $mathsConfig->id, 'term_id' => $term->id, 'title' => 'Devoir', 'max_score' => 20, 'weight' => 1]);
        $frenchAssessment = Assessment::create(['subject_config_id' => $frenchConfig->id, 'term_id' => $term->id, 'title' => 'Devoir', 'max_score' => 20, 'weight' => 1]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $service = app(GradeCalculationService::class);
        $service->saveGrade($mathsAssessment, $student, 15);
        $service->saveGrade($frenchAssessment, $student, 12);

        $result = $service->calculate($student, $term);

        $this->assertEqualsWithDelta((15 * 4 + 12 * 3) / 7 / 20 * 100, $result['summary']['average'], 0.000001);
    }

    public function test_quick_grade_entry_saves_repeater_rows_with_hidden_student_ids(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $this->actingAs($director);

        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $class = ClassRoom::create(['academic_year_id' => $year->id, 'name' => '6e A']);
        $subject = Subject::create(['name' => 'Mathématiques']);
        $config = SubjectConfig::create([
            'subject_id' => $subject->id,
            'academic_year_id' => $year->id,
            'class_room_id' => $class->id,
            'grading_method' => 'weighted_average',
            'max_score' => 20,
            'weight' => 1,
        ]);
        $assessment = Assessment::create([
            'subject_config_id' => $config->id,
            'term_id' => $term->id,
            'title' => 'Devoir',
            'max_score' => 20,
            'weight' => 1,
        ]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        Enrollment::create([
            'student_id' => $student->id,
            'class_room_id' => $class->id,
            'academic_year_id' => $year->id,
            'enrollment_date' => '2026-09-01',
            'status' => 'active',
        ]);

        Livewire::test(GradeEntry::class)
            ->set('data.assessment_id', $assessment->id)
            ->set('data.grades', [['student_id' => $student->id, 'score' => '15']])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => 15,
        ]);

        Livewire::actingAs($director)
            ->test(ListGrades::class)
            ->assertSee('75%');
    }
}
