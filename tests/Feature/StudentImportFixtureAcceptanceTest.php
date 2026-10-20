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

class StudentImportFixtureAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_synthetic_csv_fixtures_have_documented_preview_counts(): void
    {
        Storage::fake('local');

        $expected = [
            '01_cycles_complets.csv' => ['total' => 28, 'ready' => 20, 'warnings' => 4, 'errors' => 5, 'duplicates' => 2, 'ignored' => 1],
            '03_entete_decale_et_conflits.csv' => ['total' => 9, 'ready' => 4, 'warnings' => 3, 'errors' => 3, 'duplicates' => 2, 'ignored' => 0],
        ];
        foreach ($expected as $fixture => $countsExpected) {
            [$actor, $year, $class] = $this->schoolContext();
            $file = new UploadedFile(
                base_path('tests/Fixtures/onb01/'.$fixture),
                $fixture,
                'text/csv',
                null,
                true,
            );
            $service = app(StudentImportBatchService::class);
            $batch = $service->create($file, $actor, $year->id, $class->id);
            $analysis = $service->analyze($batch, $actor);
            $service->map($batch, $actor, collect($analysis['mapping'])->mapWithKeys(
                fn (array $column, string $field): array => [$field => $column['column_index']]
            )->all());
            $result = $service->simulate($batch->fresh(), $actor);
            $counts = $batch->fresh()->rows()->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')->pluck('aggregate', 'status')->all();

            $this->assertSame($countsExpected['total'], $analysis['total_rows']);
            $this->assertSame($countsExpected, $result);
            $this->assertSame($analysis['total_rows'], array_sum($counts));
        }
    }

    /** @return array{User, AcademicYear, ClassRoom} */
    private function schoolContext(): array
    {
        auth()->logout();
        $school = School::factory()->create();
        $actor = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $this->actingAs($actor);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'CM2']);

        return [$actor, $year, $class];
    }
}
