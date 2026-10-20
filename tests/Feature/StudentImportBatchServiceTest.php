<?php

namespace Tests\Feature;

use App\Enums\StudentImportStatus;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentImportRow;
use App\Models\User;
use App\Services\StudentImportBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StudentImportBatchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_stages_then_creates_student_guardian_and_enrollment_idempotently(): void
    {
        Storage::fake('local');
        [$school, $actor, $year, $class] = $this->schoolContext();
        $service = app(StudentImportBatchService::class);
        $batch = $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable',
            'SYN-001,DÉMO,Ama,12/05/2014,F,6e A,90000001,Yawa Démo',
        ]), $actor, $year->id, $class->id);

        $this->assertSame(StudentImportStatus::Uploaded->value, $batch->status);
        $analysis = $service->analyze($batch, $actor);
        $this->assertSame(1, $analysis['total_rows']);

        $service->map($batch, $actor, collect($analysis['mapping'])->mapWithKeys(
            fn (array $column, string $field): array => [$field => $column['column_index']]
        )->all());
        $preview = $service->simulate($batch->fresh(), $actor);
        $this->assertSame(1, $preview['ready']);
        $this->assertSame(0, $preview['errors']);

        $result = $service->processNextChunk($batch->fresh(), $actor);
        $this->assertSame(StudentImportStatus::Completed->value, $result['status']);
        $this->assertSame(1, Student::query()->count());
        $this->assertSame(1, Guardian::query()->count());
        $this->assertDatabaseHas('enrollments', [
            'student_id' => Student::query()->value('id'),
            'academic_year_id' => $year->id,
            'class_room_id' => $class->id,
        ]);
        $this->assertSame(1, $batch->fresh()->created_count);
        $this->assertDatabaseMissing('audit_logs', ['new_values->first_name' => 'Ama']);
        $this->assertDatabaseMissing('audit_logs', ['new_values->phone' => '+22890000001']);
    }

    public function test_invalid_rows_are_reported_without_rolling_back_valid_rows(): void
    {
        Storage::fake('local');
        [$school, $actor, $year, $class] = $this->schoolContext();
        $service = app(StudentImportBatchService::class);
        $batch = $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable',
            'SYN-001,DÉMO,Ama,12/05/2014,F,6e A,90000001,Yawa Démo',
            'SYN-002,DÉMO,Komi,12/05/2014,M,6e A,900025,Yawa Démo',
        ]), $actor, $year->id, $class->id);
        $analysis = $service->analyze($batch, $actor);
        $service->map($batch, $actor, collect($analysis['mapping'])->mapWithKeys(
            fn (array $column, string $field): array => [$field => $column['column_index']]
        )->all());
        $this->assertSame(1, $service->simulate($batch->fresh(), $actor)['errors']);

        $service->processNextChunk($batch->fresh(), $actor);

        $this->assertSame(1, Student::query()->count());
        $this->assertSame(StudentImportStatus::Completed->value, $batch->fresh()->status);
        $this->assertSame(1, $batch->fresh()->failed_count);
    }

    public function test_exact_duplicate_waits_for_human_decision_before_update(): void
    {
        Storage::fake('local');
        [$school, $actor, $year, $class] = $this->schoolContext();
        $existing = Student::create([
            'school_id' => $school->id, 'student_number' => 'OLD-001', 'admission_number' => 'OLD-001',
            'first_name' => 'Ama', 'last_name' => 'DÉMO', 'date_of_birth' => '2014-05-12',
        ]);

        $service = app(StudentImportBatchService::class);
        $batch = $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable',
            'NEW-001,DÉMO,Ama,12/05/2014,F,6e A,90000001,Yawa Démo',
        ]), $actor, $year->id, $class->id);
        $analysis = $service->analyze($batch, $actor);
        $service->map($batch, $actor, collect($analysis['mapping'])->mapWithKeys(
            fn (array $column, string $field): array => [$field => $column['column_index']]
        )->all());
        $this->assertSame(1, $service->simulate($batch->fresh(), $actor)['duplicates']);

        $service->processNextChunk($batch->fresh(), $actor);
        $this->assertSame(StudentImportStatus::Partial->value, $batch->fresh()->status);
        $row = StudentImportRow::query()->where('student_import_batch_id', $batch->id)->firstOrFail();
        $service->resolveDuplicate($row, $actor, 'update', $existing->id);
        $service->processNextChunk($batch->fresh(), $actor);

        $this->assertSame(1, Student::query()->count());
        $this->assertSame(1, $batch->fresh()->updated_count);
        $this->assertSame(StudentImportStatus::Completed->value, $batch->fresh()->status);
    }

    public function test_import_resumes_in_chunks_and_replaying_completed_batch_is_idempotent(): void
    {
        Storage::fake('local');
        config(['student-import.chunk_size' => 1]);
        [$school, $actor, $year, $class] = $this->schoolContext();
        $service = app(StudentImportBatchService::class);
        $batch = $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable',
            'SYN-001,DÉMO,Ama,12/05/2014,F,6e A,90000001,Yawa Démo',
            'SYN-002,AKAKPO,Komi,14/05/2014,M,6e A,90000002,Kossi Akakpo',
        ]), $actor, $year->id, $class->id);
        $analysis = $service->analyze($batch, $actor);
        $service->map($batch, $actor, collect($analysis['mapping'])->mapWithKeys(
            fn (array $column, string $field): array => [$field => $column['column_index']]
        )->all());
        $service->simulate($batch->fresh(), $actor);

        $first = $service->processNextChunk($batch->fresh(), $actor);
        $second = $service->processNextChunk($batch->fresh(), $actor);
        $replay = $service->processNextChunk($batch->fresh(), $actor);

        $this->assertSame(1, $first['remaining']);
        $this->assertSame(StudentImportStatus::Completed->value, $second['status']);
        $this->assertSame(0, $replay['processed']);
        $this->assertSame(2, Student::query()->count());
    }

    public function test_missing_matricule_uses_the_school_sequence_only_when_a_row_is_created(): void
    {
        Storage::fake('local');
        [$school, $actor, $year, $class] = $this->schoolContext();
        $service = app(StudentImportBatchService::class);
        $batch = $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable',
            ',DÉMO,Ama,12/05/2014,F,CM2,90000001,Yawa Démo',
        ]), $actor, $year->id, $class->id);
        $analysis = $service->analyze($batch, $actor);
        $service->map($batch, $actor, collect($analysis['mapping'])->mapWithKeys(
            fn (array $column, string $field): array => [$field => $column['column_index']]
        )->all());
        $service->simulate($batch->fresh(), $actor);
        $service->processNextChunk($batch->fresh(), $actor);

        $this->assertSame('STU-000001', Student::query()->firstOrFail()->student_number);
    }

    public function test_only_one_nonterminal_import_can_be_active_for_a_school(): void
    {
        Storage::fake('local');
        [$school, $actor, $year, $class] = $this->schoolContext();
        $service = app(StudentImportBatchService::class);
        $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable',
            'ONE-001,DÉMO,Ama,12/05/2014,F,CM2,90000001,Yawa Démo',
        ]), $actor, $year->id, $class->id);

        $this->expectException(ValidationException::class);
        $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable',
            'TWO-001,AKAKPO,Komi,13/05/2014,M,CM2,90000002,Kossi Akakpo',
        ]), $actor, $year->id, $class->id);
    }

    public function test_upload_rate_limit_is_enforced_per_school_user(): void
    {
        Storage::fake('local');
        [$school, $actor, $year, $class] = $this->schoolContext();
        $key = "student-import:upload:{$school->id}:{$actor->id}";
        RateLimiter::clear($key);
        for ($attempt = 0; $attempt < config('student-import.uploads_per_hour'); $attempt++) {
            RateLimiter::hit($key, 3600);
        }

        $this->expectException(ValidationException::class);
        app(StudentImportBatchService::class)->create($this->csvFile([
            'Matricule,Nom,Prénom,Tél parent,Responsable',
            'RATE-001,TEST,Ama,90123456,Parent Test',
        ]), $actor, $year->id, $class->id);
    }

    public function test_import_can_be_cancelled_before_processing_and_its_private_file_is_deleted(): void
    {
        Storage::fake('local');
        [$school, $actor, $year, $class] = $this->schoolContext();
        $service = app(StudentImportBatchService::class);
        $batch = $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Tél parent,Responsable',
            'CANCEL-001,TEST,Ama,90123456,Parent Test',
        ]), $actor, $year->id, $class->id);
        $path = $batch->file_path;

        $service->cancel($batch, $actor);

        $this->assertSame('cancelled', $batch->fresh()->status);
        $this->assertNull($batch->fresh()->file_path);
        $this->assertFalse(Storage::disk('local')->exists($path));
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $batch->id,
            'action' => 'student_import.cancelled',
        ]);

        $replacement = $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Tél parent,Responsable',
            'CANCEL-001,TEST,Ama,90123456,Parent Test',
        ]), $actor, $year->id, $class->id);
        $this->assertNotSame($batch->id, $replacement->id);
        $this->assertSame('uploaded', $replacement->status);
    }

    public function test_import_with_created_students_cannot_be_cancelled_or_rolled_back(): void
    {
        Storage::fake('local');
        [$school, $actor, $year, $class] = $this->schoolContext();
        $service = app(StudentImportBatchService::class);
        $batch = $service->create($this->csvFile([
            'Matricule,Nom,Prénom,Tél parent,Responsable',
            'KEEP-001,TEST,Ama,90123456,Parent Test',
        ]), $actor, $year->id, $class->id);
        $analysis = $service->analyze($batch, $actor);
        $service->map($batch, $actor, collect($analysis['mapping'])->mapWithKeys(
            fn (array $column, string $field): array => [$field => $column['column_index']]
        )->all());
        $service->simulate($batch->fresh(), $actor);
        $service->processNextChunk($batch->fresh(), $actor);
        $path = $batch->fresh()->file_path;

        try {
            $service->cancel($batch->fresh(), $actor);
            $this->fail('An import that created students must not be rolled back by cancellation.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('aucune ligne déjà créée ou modifiée', $exception->getMessage());
        }

        $this->assertSame(1, Student::query()->count());
        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    /** @return array{School, User, AcademicYear, ClassRoom} */
    private function schoolContext(): array
    {
        $school = School::factory()->create();
        $actor = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $this->actingAs($actor);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => '6e A']);

        return [$school, $actor, $year, $class];
    }

    /** @param list<string> $rows */
    private function csvFile(array $rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('roster.csv', implode("\n", $rows));
    }
}
