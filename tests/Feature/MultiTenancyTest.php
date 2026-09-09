<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_scope_hides_other_school_records(): void
    {
        $one = School::factory()->create();
        $two = School::factory()->create();
        $user = User::factory()->create(['school_id' => $one->id]);
        Student::factory()->create(['school_id' => $one->id]);
        Student::factory()->create(['school_id' => $two->id]);
        $this->actingAs($user);
        $this->assertCount(1, Student::all());
        $this->assertTrue(Student::withoutGlobalScopes()->where('school_id', $two->id)->exists());
    }

    public function test_creating_a_student_uses_authenticated_school(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id]));
        $student = Student::create(['admission_number' => 'AUTO-1', 'first_name' => 'A', 'last_name' => 'Student']);
        $this->assertSame($school->id, $student->school_id);
    }
}
