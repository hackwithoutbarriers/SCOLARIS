<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectConfig;
use App\Models\Term;
use App\Models\User;
use App\Services\GradeCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_named_grade_csv_import_validates_tenant_max_score_and_reports_duplicates(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id]));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $subject = Subject::create(['name' => 'Mathématiques']);
        $config = SubjectConfig::create(['subject_id' => $subject->id, 'academic_year_id' => $year->id, 'grading_method' => 'weighted_average', 'passing_score' => 50, 'max_score' => 20, 'weight' => 1]);
        $assessment = Assessment::create(['subject_config_id' => $config->id, 'term_id' => $term->id, 'title' => 'Devoir 1', 'max_score' => 20, 'weight' => 1]);
        $student = Student::factory()->create(['school_id' => $school->id, 'student_number' => 'STU-001']);
        $path = tempnam(sys_get_temp_dir(), 'grades-').'.csv';
        file_put_contents($path, "student_number,subject,assessment,score,max_score\nSTU-001,Mathématiques,Devoir 1,15,20\nSTU-001,Mathématiques,Devoir 1,16,20\nSTU-001,Mathématiques,Devoir 1,18,10\n");

        $result = app(GradeCsvImporter::class)->import($path, $school->id);

        @unlink($path);
        $this->assertSame(3, $result['analyzed']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['duplicates']);
        $this->assertSame(1, $result['errors']);
        $this->assertSame(16.0, (float) $student->grades()->first()->score);
        $this->assertSame(1, $assessment->grades()->count());
    }
}
