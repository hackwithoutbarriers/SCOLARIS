<?php

namespace Tests\Unit;

use App\Services\StudentCsvAnalyzer;
use InvalidArgumentException;
use Tests\TestCase;

class StudentCsvAnalyzerTest extends TestCase
{
    public function test_it_rejects_empty_and_header_only_csv_files(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'student-import-test-');
        $this->assertNotFalse($path);

        file_put_contents($path, '');
        try {
            app(StudentCsvAnalyzer::class)->analyze($path);
            $this->fail('An empty CSV must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('vide', $exception->getMessage());
        }

        file_put_contents($path, "Matricule,Nom,Prénom,Tél parent,Responsable\n");
        try {
            app(StudentCsvAnalyzer::class)->analyze($path);
            $this->fail('A CSV containing only headers must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('aucune ligne', $exception->getMessage());
        } finally {
            unlink($path);
        }
    }

    public function test_it_enforces_the_configured_row_limit(): void
    {
        config(['student-import.max_rows' => 1]);
        $path = tempnam(sys_get_temp_dir(), 'student-import-test-');
        $this->assertNotFalse($path);
        file_put_contents($path, "Matricule,Nom,Prénom,Tél parent,Responsable\n1,Test,Ama,90000000,Parent\n2,Test,Komi,90000001,Parent\n");

        try {
            app(StudentCsvAnalyzer::class)->analyze($path);
            $this->fail('A CSV over the configured row limit must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('plafond de lignes', $exception->getMessage());
        } finally {
            unlink($path);
        }
    }

    public function test_it_detects_a_delayed_header_and_proposes_french_field_mappings(): void
    {
        $result = app(StudentCsvAnalyzer::class)->analyze(base_path('tests/Fixtures/onb01/01_cycles_complets.csv'));

        $this->assertSame(';', $result['delimiter']);
        $this->assertSame(3, $result['header_row']);
        $this->assertSame(28, $result['total_rows']);
        $this->assertSame(20, count($result['preview']));
        $this->assertSame('last_name', $result['mapping']['last_name']['field']);
        $this->assertSame('N° matricule', $result['mapping']['student_number']['label']);
        $this->assertGreaterThanOrEqual(0.8, $result['mapping']['first_name']['confidence']);
        $this->assertContains('Colonne inutile', $result['ignored_columns']);
    }

    public function test_it_rejects_non_utf8_and_xlsx_inputs_with_actionable_messages(): void
    {
        try {
            app(StudentCsvAnalyzer::class)->analyze(base_path('tests/Fixtures/onb01/02_secondaire_windows1252.csv'));
            $this->fail('Windows-1252 must not be silently transcoded in the CSV MVP.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('UTF-8', $exception->getMessage());
        }

        try {
            app(StudentCsvAnalyzer::class)->analyze(base_path('tests/Fixtures/onb01/04_multi_feuilles.xlsx'));
            $this->fail('XLSX is outside the CSV MVP.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('CSV', $exception->getMessage());
        }
    }
}
