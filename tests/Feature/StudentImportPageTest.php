<?php

namespace Tests\Feature;

use App\Filament\Pages\StudentImport;
use App\Filament\Pages\SchoolOnboarding;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StudentImportPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_download_a_synthetic_csv_template(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        Livewire::actingAs($director)
            ->test(StudentImport::class)
            ->call('downloadTemplate')
            ->assertFileDownloaded('modele-import-eleves.csv');
    }

    public function test_import_rows_can_be_filtered_by_warning_status(): void
    {
        Storage::fake('local');
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'CM2']);

        $page = Livewire::actingAs($director)
            ->test(StudentImport::class)
            ->set('data.file', UploadedFile::fake()->createWithContent('warnings.csv', implode("\n", [
                'Matricule,Nom,Prénom,Date de naissance,Tél parent,Responsable',
                'WARN-001,DÉMO,Ama,12-05-18,90000001,Yawa Démo',
                'OK-001,TEST,Komi,2014-05-12,90000002,Kossi Test',
            ])))
            ->set('data.academic_year_id', $year->id)
            ->set('data.class_room_id', $class->id)
            ->call('analyze')
            ->call('simulate')
            ->set('rowFilter', 'warnings');

        $this->assertSame(1, $page->instance()->rows()->total());
    }

    public function test_header_only_csv_is_rejected_with_an_actionable_page_state(): void
    {
        Storage::fake('local');
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'CM2']);

        Livewire::actingAs($director)
            ->test(StudentImport::class)
            ->set('data.file', UploadedFile::fake()->createWithContent('empty-roster.csv', 'Matricule,Nom,Prénom,Tél parent,Responsable'))
            ->set('data.academic_year_id', $year->id)
            ->set('data.class_room_id', $class->id)
            ->call('analyze');

        $this->assertDatabaseHas('student_import_batches', [
            'school_id' => $school->id,
            'status' => 'uploaded',
        ]);
    }

    public function test_director_can_complete_the_guided_import_page_flow(): void
    {
        Storage::fake('local');
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'CM2']);

        Livewire::actingAs($director)
            ->test(StudentImport::class)
            ->set('data.file', UploadedFile::fake()->createWithContent('classe.csv', implode("\n", [
                'Matricule,Nom,Prénom,Date de naissance,Sexe,Classe,Tél parent,Responsable',
                'PILOTE-001,DÉMO,Ama,12/05/2014,F,CM2,90000001,Yawa Démo',
            ])))
            ->set('data.academic_year_id', $year->id)
            ->set('data.class_room_id', $class->id)
            ->call('analyze')
            ->assertSet('analysis.header_row', 1)
            ->call('simulate')
            ->assertSet('simulation.ready', 1)
            ->call('processNextChunk');

        $this->assertSame(1, Student::query()->count());
        $this->assertDatabaseHas('enrollments', ['academic_year_id' => $year->id, 'class_room_id' => $class->id]);
        $this->assertDatabaseHas('student_import_batches', ['status' => 'completed', 'created_count' => 1]);
    }

    public function test_active_import_is_restored_when_the_director_returns_to_the_page(): void
    {
        Storage::fake('local');
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-2027', 'status' => 'active',
            'is_current' => true, 'start_date' => '2026-09-01', 'end_date' => '2027-07-01',
        ]);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'CM2']);

        $initialPage = Livewire::actingAs($director)
            ->test(StudentImport::class)
            ->set('data.file', UploadedFile::fake()->createWithContent('reprise.csv', implode("\n", [
                'Matricule,Nom,Prénom,Date de naissance,Tél parent,Responsable',
                'REPRISE-001,DÉMO,Ama,12/05/2014,90000001,Yawa Démo',
            ])))
            ->set('data.academic_year_id', $year->id)
            ->set('data.class_room_id', $class->id)
            ->call('analyze')
            ->call('simulate');
        $batchId = $initialPage->instance()->batchId;

        Livewire::actingAs($director)
            ->test(StudentImport::class)
            ->assertSet('batchId', $batchId)
            ->assertSet('simulation.ready', 1)
            ->assertSet('analysis.mapping.last_name.column_index', 1);
    }

    public function test_onboarding_shows_one_direct_recommendation_for_the_next_incomplete_step(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        Livewire::actingAs($director)
            ->test(SchoolOnboarding::class)
            ->assertSee('Prochaine action recommandée')
            ->assertSee('Année scolaire et périodes')
            ->assertSee('Continuer cette étape');
    }
}
