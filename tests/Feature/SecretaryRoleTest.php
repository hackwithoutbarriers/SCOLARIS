<?php

namespace Tests\Feature;

use App\Filament\Resources\AssessmentResource;
use App\Filament\Resources\EnrollmentResource;
use App\Filament\Resources\GradeResource;
use App\Filament\Resources\GuardianResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\StudentResource;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_manage_people_and_enrollments_but_not_academic_or_finance_workflows(): void
    {
        $school = School::factory()->create();
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);

        $this->actingAs($secretary);

        $this->assertTrue($secretary->isSecretary());
        $this->assertTrue(StudentResource::canViewAny());
        $this->assertTrue(GuardianResource::canViewAny());
        $this->assertTrue(EnrollmentResource::canViewAny());
        $this->assertFalse(GradeResource::canViewAny());
        $this->assertFalse(AssessmentResource::canViewAny());
        $this->assertFalse(PaymentResource::canViewAny());

        $this->get('/admin/students')->assertOk();
        $this->get('/admin/guardians')->assertOk();
        $this->get('/admin/enrollments')->assertOk();
        $this->get('/admin/grades')->assertForbidden();
        $this->get('/admin/assessments')->assertForbidden();
        $this->get('/admin/payments')->assertForbidden();
        $this->get('/admin/grade-entry')->assertForbidden();
        $this->get('/admin/collection-center')->assertForbidden();
    }

    public function test_secretary_cannot_access_another_school_record(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);

        $this->assertFalse($secretary->can('view', $student));
    }

    public function test_secretary_cannot_read_academic_grades_through_the_api(): void
    {
        $school = School::factory()->create();
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
        ]);
        $student->enrollments()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'class_room_id' => $class->id,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($secretary)
            ->getJson('/api/academic/students/'.$student->id.'/grades')
            ->assertForbidden();
    }
}
