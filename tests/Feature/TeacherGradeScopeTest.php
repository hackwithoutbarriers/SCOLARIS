<?php

namespace Tests\Feature;

use App\Filament\Resources\AssessmentResource;
use App\Filament\Resources\GradeResource;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectConfig;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherGradeScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_resources_only_return_assessments_and_grades_in_their_assignment(): void
    {
        [$school, $year, $term, $subject, $class, $student, $teacher] = $this->fixture();
        $otherTeacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $config = SubjectConfig::create([
            'school_id' => $school->id, 'subject_id' => $subject->id, 'academic_year_id' => $year->id,
            'class_room_id' => $class->id, 'grading_method' => 'weighted_average', 'weight' => 1, 'max_score' => 20,
        ]);
        $owned = Assessment::create(['school_id' => $school->id, 'subject_config_id' => $config->id, 'term_id' => $term->id, 'teacher_id' => $teacher->id, 'title' => 'Devoir autorisé', 'max_score' => 20, 'weight' => 1]);
        $other = Assessment::create(['school_id' => $school->id, 'subject_config_id' => $config->id, 'term_id' => $term->id, 'teacher_id' => $otherTeacher->id, 'title' => 'Devoir interdit', 'max_score' => 20, 'weight' => 1]);
        $owned->grades()->create(['school_id' => $school->id, 'student_id' => $student->id, 'score' => 12]);
        $other->grades()->create(['school_id' => $school->id, 'student_id' => $student->id, 'score' => 8]);

        $this->actingAs($teacher);

        $this->assertSame([$owned->id], AssessmentResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$owned->grades()->first()->id], GradeResource::getEloquentQuery()->pluck('id')->all());
    }

    private function fixture(): array
    {
        $school = School::factory()->create();
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Période 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathématiques']);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => '6e A', 'capacity' => 30]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        Enrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'class_room_id' => $class->id, 'academic_year_id' => $year->id, 'enrollment_date' => '2026-09-01', 'status' => 'active']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'class_room_id' => $class->id, 'subject_id' => $subject->id, 'academic_year_id' => $year->id]);

        return [$school, $year, $term, $subject, $class, $student, $teacher];
    }
}
