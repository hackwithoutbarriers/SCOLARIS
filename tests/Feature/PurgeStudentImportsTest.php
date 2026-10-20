<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\StudentImportBatch;
use App\Models\StudentImportRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeStudentImportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_private_file_and_staging_data_are_purged(): void
    {
        Storage::fake('local');
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'CM2']);
        $path = 'student-imports/expired.csv';
        Storage::disk('local')->put($path, 'synthetic');
        $batch = StudentImportBatch::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'class_room_id' => $class->id,
            'created_by' => $director->id, 'file_name' => 'expired.csv', 'file_sha256' => hash('sha256', 'synthetic'),
            'file_path' => $path, 'status' => 'completed', 'expires_at' => now()->subDay(),
        ]);
        StudentImportRow::create([
            'school_id' => $school->id, 'student_import_batch_id' => $batch->id, 'row_number' => 2,
            'status' => 'error', 'normalized_data' => ['first_name' => 'Synthetic'],
        ]);

        Artisan::call('student-imports:purge');

        $this->assertFalse(Storage::disk('local')->exists($path));
        $this->assertNull($batch->fresh()->file_path);
        $this->assertSame(0, StudentImportRow::query()->where('student_import_batch_id', $batch->id)->count());
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => StudentImportBatch::class, 'action' => 'student_import.purged']);
    }
}
