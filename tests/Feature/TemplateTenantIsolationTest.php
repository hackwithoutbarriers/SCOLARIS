<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ReportCardTemplate;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\ReportCard;
use App\Services\ReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TemplateTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_a_cannot_preview_or_generate_school_b_template(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $userA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'director']);
        $templateB = ReportCardTemplate::create(['school_id' => $schoolB->id, 'name' => 'B', 'schema' => ReportCardTemplate::defaultSchema()]);
        $studentB = Student::factory()->create(['school_id' => $schoolB->id]);
        $yearB = AcademicYear::create(['school_id' => $schoolB->id, 'name' => '2026-2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-08-31']);

        $cardB = ReportCard::create(['school_id' => $schoolB->id, 'student_id' => $studentB->id, 'academic_year_id' => $yearB->id, 'template_id' => $templateB->id, 'version' => 1, 'status' => 'draft', 'data' => ['student' => ['name' => 'B'], 'period' => [], 'subjects' => [], 'summary' => []]]);
        $this->actingAs($userA)->get(route('report-cards.html', $cardB->id))->assertNotFound();
        $this->actingAs($userA)->get(route('report-cards.pdf', $cardB->id))->assertNotFound();
        $this->assertTrue(Gate::forUser($userA)->denies('view', $templateB));
        $this->assertTrue(Gate::forUser($userA)->denies('update', $templateB));
        $this->assertTrue(Gate::forUser($userA)->denies('delete', $templateB));
        try {
            $templateB->update(['status' => 'ACTIVE']);
            $this->fail('A tenant must not activate another school template.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
        $this->expectException(AuthorizationException::class);
        app(ReportCardService::class)->generate($studentB, $yearB, null, $templateB);
    }
}
