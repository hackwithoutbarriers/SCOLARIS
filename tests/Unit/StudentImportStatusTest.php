<?php

namespace Tests\Unit;

use App\Enums\StudentImportStatus;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StudentImportStatusTest extends TestCase
{
    public function test_workflow_allows_analysis_mapping_preview_processing_and_resume(): void
    {
        $this->assertTrue(StudentImportStatus::Uploaded->canTransitionTo(StudentImportStatus::Analyzed));
        $this->assertTrue(StudentImportStatus::Analyzed->canTransitionTo(StudentImportStatus::Mapped));
        $this->assertTrue(StudentImportStatus::Mapped->canTransitionTo(StudentImportStatus::Previewed));
        $this->assertTrue(StudentImportStatus::Previewed->canTransitionTo(StudentImportStatus::Processing));
        $this->assertTrue(StudentImportStatus::Processing->canTransitionTo(StudentImportStatus::Partial));
        $this->assertTrue(StudentImportStatus::Partial->canTransitionTo(StudentImportStatus::Processing));
        $this->assertTrue(StudentImportStatus::Processing->canTransitionTo(StudentImportStatus::Completed));
    }

    public function test_workflow_rejects_skipped_states_and_changes_after_completion(): void
    {
        $this->assertFalse(StudentImportStatus::Uploaded->canTransitionTo(StudentImportStatus::Completed));
        $this->assertFalse(StudentImportStatus::Completed->canTransitionTo(StudentImportStatus::Processing));
        $this->assertFalse(StudentImportStatus::Cancelled->canTransitionTo(StudentImportStatus::Processing));
        $this->expectException(RuntimeException::class);
        StudentImportStatus::Uploaded->assertCanTransitionTo(StudentImportStatus::Completed);
    }
}
