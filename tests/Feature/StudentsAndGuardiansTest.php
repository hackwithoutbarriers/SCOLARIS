<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentsAndGuardiansTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_have_multiple_guardians_with_primary_flag(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id]));
        $student = Student::create(['admission_number' => 'STU-1', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $first = Guardian::create(['name' => 'Guardian One', 'phone' => '111']);
        $second = Guardian::create(['name' => 'Guardian Two', 'phone' => '222']);
        $student->guardians()->attach($first, ['is_primary' => true]);
        $student->guardians()->attach($second, ['is_primary' => false]);
        $this->assertCount(2, $student->fresh()->guardians);
        $this->assertTrue((bool) $student->fresh()->guardians->firstWhere('id', $first->id)->pivot->is_primary);
    }

    public function test_students_and_guardians_can_be_imported_from_csv(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id]));
        $path = tempnam(sys_get_temp_dir(), 'scolaris-');

        file_put_contents($path, implode("\n", [
            'admission_number,first_name,last_name,date_of_birth,guardian_name,guardian_phone,guardian_relationship',
            'CSV-1,Marie,Curie,1987-11-07,Pierre Curie,555-0100,Parent',
        ]));

        try {
            $this->assertSame(1, app(StudentCsvImporter::class)->import($path, $school->id));
            $student = Student::where('admission_number', 'CSV-1')->firstOrFail();

            $this->assertSame('Marie Curie', $student->full_name);
            $this->assertSame('Pierre Curie', $student->guardians()->firstOrFail()->name);
        } finally {
            @unlink($path);
        }
    }
}
