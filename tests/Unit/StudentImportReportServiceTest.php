<?php

namespace Tests\Unit;

use App\Services\StudentImportReportService;
use PHPUnit\Framework\TestCase;

class StudentImportReportServiceTest extends TestCase
{
    public function test_csv_cells_that_could_be_spreadsheet_formulas_are_neutralized(): void
    {
        foreach (['=1+1', '+SUM(A1:A2)', '-2+3', '@cmd', " \t=1+1"] as $value) {
            $this->assertSame("'".$value, StudentImportReportService::safeCell($value));
        }

        $this->assertSame('Ama DÉMO', StudentImportReportService::safeCell('Ama DÉMO'));
    }
}
