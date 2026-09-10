<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Payments\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionalFeesTest extends TestCase
{
    use RefreshDatabase;

    public function test_optional_fee_is_invoiced_only_when_selected_on_enrollment(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027-2028']);
        $structure = FeeStructure::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Services']);
        Fee::create(['school_id' => $school->id, 'fee_structure_id' => $structure->id, 'name' => 'Scolarité', 'amount' => 100000, 'type' => 'tuition', 'mandatory' => true]);
        Fee::create(['school_id' => $school->id, 'fee_structure_id' => $structure->id, 'name' => 'Transport', 'amount' => 20000, 'type' => 'transport', 'mandatory' => false]);
        $selected = Student::factory()->create(['school_id' => $school->id]);
        Enrollment::create(['school_id' => $school->id, 'student_id' => $selected->id, 'class_room_id' => $structure->class_room_id ?: \App\Models\ClassRoom::factory()->create(['school_id' => $school->id])->id, 'academic_year_id' => $year->id, 'enrollment_date' => today(), 'status' => 'active', 'optional_fee_types' => ['transport']]);
        $this->assertCount(2, app(InvoiceService::class)->generateForStudent($structure, $selected)->items);
    }
}
