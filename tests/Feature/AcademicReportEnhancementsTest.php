<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\MentionThreshold;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectConfig;
use App\Models\Term;
use App\Models\User;
use App\Services\GradeCalculationService;
use App\Services\ReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicReportEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_competitive_class_ranking_is_provisional_until_validation(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'T1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20']);
        $class = ClassRoom::create(['academic_year_id' => $year->id, 'name' => '6e', 'cycle' => 'secondaire 1']);
        $students = Student::factory()->count(4)->create(['school_id' => $school->id]);
        foreach ($students as $student) {
            Enrollment::create(['student_id' => $student->id, 'class_room_id' => $class->id, 'academic_year_id' => $year->id, 'enrollment_date' => '2026-09-01', 'status' => 'active']);
        }
        $subject = Subject::create(['name' => 'Mathématiques']);
        $config = SubjectConfig::create(['subject_id' => $subject->id, 'academic_year_id' => $year->id, 'grading_method' => 'weighted_average', 'weight' => 1, 'max_score' => 20]);
        $assessment = Assessment::create(['subject_config_id' => $config->id, 'term_id' => $term->id, 'title' => 'Devoir', 'max_score' => 20, 'weight' => 1]);
        $calculator = app(GradeCalculationService::class);
        foreach ([20, 18, 18, 10] as $index => $score) {
            $calculator->saveGrade($assessment, $students[$index], $score);
        }

        $this->assertSame(4, $calculator->rankings($students[3], $term)['class_rank']);
        $this->assertTrue($calculator->rankings($students[3], $term)['provisional']);
        $calculator->validateTerm($term);
        $this->assertFalse($calculator->rankings($students[3], $term)['provisional']);
    }

    public function test_mentions_have_no_implicit_threshold_and_conduct_is_configurable(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $this->assertSame([], app(GradeCalculationService::class)->mentionFor($student, $year, 95));
        MentionThreshold::create(['academic_year_id' => $year->id, 'label' => 'Distinction', 'minimum_score' => 90]);
        $this->assertSame('Distinction', app(GradeCalculationService::class)->mentionFor($student, $year, 95)['label']);
        $this->assertSame(['Bonne conduite', 'Conduite à surveiller', 'Avertissement conduite', 'Blâme'], $school->conductLabels()->orderBy('sort_order')->pluck('label')->all());
    }

    public function test_mention_override_is_audited_and_locked_after_publication(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'T1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20']);
        $card = app(ReportCardService::class)->generate(Student::factory()->create(['school_id' => $school->id]), $year, $term);
        app(ReportCardService::class)->overrideAcademicMention($card, 'Distinction', 'Décision pédagogique');
        $service = app(ReportCardService::class);
        $service->publish($service->approve($service->submitToClassCouncil($service->submitForReview($card->refresh()))));
        $this->expectException(ValidationException::class);
        app(ReportCardService::class)->overrideAcademicMention($card->refresh(), 'Blâme', 'Modification tardive');
    }
}
