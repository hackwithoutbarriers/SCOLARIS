<?php

namespace Tests\Feature;

use App\Filament\Pages\StudentImport;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\StudentImportBatch;
use App\Models\StudentImportRow;
use App\Models\User;
use App\Services\StudentImportBatchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class StudentImportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_director_and_secretary_can_start_and_view_imports(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        foreach ([$director, $secretary] as $user) {
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', StudentImportBatch::class));
            $this->assertTrue(Gate::forUser($user)->allows('create', StudentImportBatch::class));
        }
        foreach ([$accountant, $teacher] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('viewAny', StudentImportBatch::class));
            $this->assertFalse(Gate::forUser($user)->allows('create', StudentImportBatch::class));
        }
    }

    public function test_batch_line_and_file_workflow_refuse_cross_school_access_by_identifier(): void
    {
        $first = $this->schoolWithBatch();
        $second = $this->schoolWithBatch();
        $this->actingAs($first['director']);

        $this->assertFalse(Gate::allows('view', $second['batch']));
        $this->assertFalse(Gate::allows('view', $second['row']));
        $this->expectException(AuthorizationException::class);
        app(StudentImportBatchService::class)->analyze($second['batch'], $first['director']);
    }

    public function test_another_school_batch_cannot_be_opened_through_the_filament_url(): void
    {
        $first = $this->schoolWithBatch();
        $second = $this->schoolWithBatch();

        $this->actingAs($first['director'])
            ->get(StudentImport::getUrl(['batch' => $second['batch']->id]))
            ->assertNotFound();
    }

    /** @return array{director: User, batch: StudentImportBatch, row: StudentImportRow} */
    private function schoolWithBatch(): array
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'CM2']);
        $batch = StudentImportBatch::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'class_room_id' => $class->id,
            'created_by' => $director->id, 'file_name' => 'roster.csv', 'file_sha256' => hash('sha256', (string) $school->id),
            'status' => 'uploaded',
        ]);
        $row = StudentImportRow::create([
            'school_id' => $school->id, 'student_import_batch_id' => $batch->id, 'row_number' => 3,
        ]);

        return compact('director', 'batch', 'row');
    }
}
