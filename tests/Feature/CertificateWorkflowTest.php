<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Certificate;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_prepares_director_validates_and_secretary_records_delivery(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        $this->actingAs($secretary);
        $certificate = app(CertificateService::class)->prepare(['student_id' => $student->id, 'document_type' => 'certificate', 'workflow_note' => 'Dossier demandé']);
        $this->assertSame('draft', $certificate->status);
        app(CertificateService::class)->submit($certificate);
        $this->assertSame('pending_validation', $certificate->fresh()->status);

        $this->actingAs($director);
        $validated = app(CertificateService::class)->validate($certificate->fresh());
        $this->assertSame('validated', $validated->status);
        $this->assertSame('CERT-000001', $validated->number);

        $this->actingAs($secretary);
        $delivered = app(CertificateService::class)->deliver($validated, 'whatsapp', 'Parent de '.$student->full_name);
        $this->assertSame('delivered', $delivered->status);
    }

    public function test_director_cannot_edit_a_certificate_after_it_enters_validation(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        $this->actingAs($secretary);
        $certificate = app(CertificateService::class)->prepare([
            'student_id' => $student->id,
            'document_type' => 'certificate',
            'workflow_note' => 'Dossier demandé',
        ]);
        app(CertificateService::class)->submit($certificate);

        $this->actingAs($director)
            ->get('/admin/certificates/'.$certificate->id.'/edit')
            ->assertForbidden();
    }
}
