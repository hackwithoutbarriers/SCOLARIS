<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\User;
use App\Services\StudentImportBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentImportVolumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_thousand_rows_are_analyzed_and_staged_with_bounded_memory(): void
    {
        Storage::fake('local');
        config(['student-import.max_rows' => 2000]);
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $this->actingAs($director);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'CM2']);

        $csv = ['Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable'];
        for ($index = 1; $index <= 2000; $index++) {
            $day = 1 + ($index % 28);
            $month = 1 + ((int) floor($index / 28) % 12);
            $yearOfBirth = 2008 + ((int) floor($index / 336) % 8);
            $csv[] = sprintf(
                'SYN-%04d,ÉLÈVE%04d,Ama%04d,%02d/%02d/%04d,F,CM2,90123456,Yawa Démo',
                $index,
                $index,
                $index,
                $day,
                $month,
                $yearOfBirth,
            );
        }

        $startedAt = hrtime(true);
        $service = app(StudentImportBatchService::class);
        $batch = $service->create(
            UploadedFile::fake()->createWithContent('2000-eleves.csv', implode("\n", $csv)),
            $director,
            $year->id,
            $class->id,
        );
        $analysis = $service->analyze($batch, $director);
        $service->map($batch, $director, collect($analysis['mapping'])->mapWithKeys(
            fn (array $column, string $field): array => [$field => $column['column_index']]
        )->all());
        $result = $service->simulate($batch->fresh(), $director);
        $elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;

        $this->assertSame(2000, $analysis['total_rows']);
        $this->assertSame(2000, $result['ready']);
        $this->assertSame(0, $result['errors']);
        $this->assertSame(2000, $batch->fresh()->rows()->count());
        $this->assertLessThan(120, $elapsedSeconds, 'La simulation de 2 000 lignes dépasse le seuil de 120 secondes.');
    }
}
