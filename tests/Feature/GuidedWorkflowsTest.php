<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\GradeCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidedWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_resume_onboarding_and_teacher_cannot_access_it(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        $this->actingAs($director)->get('/admin/school-onboarding')
            ->assertOk()
            ->assertSee('Mise en route de votre école')
            ->assertSee('Élèves');
        $this->actingAs($teacher)->get('/admin/school-onboarding')->assertForbidden();
    }

    public function test_grade_preview_does_not_write_data_and_reports_invalid_rows(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id, 'student_number' => 'ST-1']);
        $path = tempnam(sys_get_temp_dir(), 'preview-').'.csv';
        file_put_contents($path, "student_number,subject,assessment,score,max_score\nST-1,Math,Devoir,15,20\n,Math,Devoir,abc,20\n");

        $preview = app(GradeCsvImporter::class)->preview($path);

        @unlink($path);
        $this->assertSame(2, $preview['analyzed']);
        $this->assertSame(1, $preview['valid']);
        $this->assertSame(1, $preview['invalid']);
        $this->assertDatabaseCount('grades', 0);
    }
}
