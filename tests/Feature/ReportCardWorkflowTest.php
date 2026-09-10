<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ReportCardTemplate;
use App\Models\School;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\ReportCardRenderer;
use App\Services\ReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportCardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_card_workflow_keeps_a_versioned_snapshot_for_each_state(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $card = app(ReportCardService::class)->generate(Student::factory()->create(['school_id' => $school->id]), $year, $term);
        app(ReportCardService::class)->submitForReview($card);
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        app(ReportCardService::class)->submitToClassCouncil($card->refresh());
        app(ReportCardService::class)->approve($card->refresh());
        app(ReportCardService::class)->publish($card->refresh());

        $this->assertSame('published', $card->refresh()->status);
        $this->assertCount(5, $card->versions);
        $this->assertSame(['draft', 'review', 'conseil_de_classe', 'approved', 'published'], $card->versions->pluck('status')->all());
    }

    public function test_approval_requires_class_council_step(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $this->actingAs($director);
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1]);
        $card = app(ReportCardService::class)->generate(Student::factory()->create(['school_id' => $school->id]), $year, $term);
        $service = app(ReportCardService::class);
        $service->submitForReview($card);

        $this->expectException(ValidationException::class);
        $service->approve($card->refresh());
    }

    public function test_report_card_template_rejects_unstructured_sections(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        $this->expectException(ValidationException::class);
        ReportCardTemplate::create(['name' => 'Invalid', 'schema' => ['sections' => ['javascript']], 'active' => true]);
    }

    public function test_same_normalized_data_renders_two_distinct_templates_and_pdf(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $portrait = ReportCardTemplate::create(['name' => 'A', 'orientation' => 'portrait', 'status' => 'ACTIVE', 'schema' => ['sections' => ['subjects'], 'subjects' => ['columns' => ['subject', 'average']]]]);
        $landscape = ReportCardTemplate::create(['name' => 'B', 'orientation' => 'landscape', 'status' => 'DRAFT', 'schema' => ['sections' => ['subjects'], 'subjects' => ['columns' => ['subject', 'average', 'weight', 'grade_letter']]]]);
        $service = app(ReportCardService::class);
        $a = $service->generate($student, $year, null, $portrait);
        $b = $service->generate($student, $year, null, $landscape);
        $renderer = app(ReportCardRenderer::class);

        $this->assertStringContainsString('@page{size:portrait', $renderer->renderHtml($a));
        $this->assertStringContainsString('@page{size:landscape', $renderer->renderHtml($b));
        $this->assertNotSame($renderer->renderHtml($a), $renderer->renderHtml($b));
        $this->assertNotEmpty($renderer->pdf($b)->output());
    }

    public function test_published_report_card_rejects_silent_data_changes(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => 'director']));
        $year = AcademicYear::create(['name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);
        $card = app(ReportCardService::class)->generate(Student::factory()->create(['school_id' => $school->id]), $year);
        $service = app(ReportCardService::class);
        $service->publish($service->approve($service->submitToClassCouncil($service->submitForReview($card))));
        $this->expectException(ValidationException::class);
        $card->update(['data' => ['tampered' => true]]);
    }
}
