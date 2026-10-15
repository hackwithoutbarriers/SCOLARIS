<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SchoolSupport;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminInterventionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_cannot_read_school_data_without_an_intervention(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);

        $this->actingAs($admin)
            ->getJson('/api/academic/students/'.$student->id.'/grades')
            ->assertForbidden();

        $this->assertFalse($admin->isFinanceOperator());
    }

    public function test_intervention_is_school_scoped_and_each_request_is_audited(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $studentA = Student::factory()->create(['school_id' => $schoolA->id]);
        $studentB = Student::factory()->create(['school_id' => $schoolB->id]);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);
        $session = [
            'scolaris_intervention.school_id' => $schoolA->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ];

        $this->withSession($session)
            ->actingAs($admin)
            ->getJson('/api/academic/students/'.$studentA->id.'/grades')
            ->assertOk();

        $this->withSession($session)
            ->actingAs($admin)
            ->get('/admin/students')
            ->assertOk();

        $this->withSession($session)
            ->actingAs($admin)
            ->getJson('/api/academic/students/'.$studentB->id.'/grades')
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $schoolA->id,
            'user_id' => $admin->id,
            'action' => 'super_admin.request_read',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'school_id' => $schoolB->id,
            'user_id' => $admin->id,
            'action' => 'super_admin.request_read',
        ]);
    }

    public function test_intervention_allows_only_scoped_student_registry_exports_and_audits_them_as_exports(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        Student::factory()->create(['school_id' => $schoolA->id, 'last_name' => 'VisibleA']);
        Student::factory()->create(['school_id' => $schoolB->id, 'last_name' => 'HiddenB']);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);
        $session = [
            'scolaris_intervention.school_id' => $schoolA->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ];

        $response = $this->withSession($session)
            ->actingAs($admin)
            ->get('/student-registry/csv')
            ->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('VisibleA', $csv);
        $this->assertStringNotContainsString('HiddenB', $csv);
        $this->withSession($session)
            ->actingAs($admin)
            ->get('/student-registry/pdf')
            ->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $schoolA->id,
            'user_id' => $admin->id,
            'action' => 'super_admin.request_export',
        ]);
    }

    public function test_expired_intervention_cannot_be_used_to_read_school_data(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);

        $this->withSession([
            'scolaris_intervention.school_id' => $school->id,
            'scolaris_intervention.expires_at' => now()->subMinute()->timestamp,
        ])
            ->actingAs($admin)
            ->getJson('/api/academic/students/'.$student->id.'/grades')
            ->assertForbidden();
    }

    public function test_intervention_without_expiry_is_rejected_and_cleared(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);

        $this->withSession(['scolaris_intervention.school_id' => $school->id])
            ->actingAs($admin)
            ->getJson('/api/academic/students/'.$student->id.'/grades')
            ->assertForbidden()
            ->assertSessionMissing('scolaris_intervention.school_id');

        $this->assertDatabaseMissing('audit_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'action' => 'super_admin.request_read',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'action' => 'super_admin.context_expired',
        ]);
    }

    public function test_student_card_pdf_is_audited_as_an_export_during_intervention(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);
        $this->withSession([
            'scolaris_intervention.school_id' => $school->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ]);

        $this->actingAs($admin)
            ->get(route('student-cards.individual', $student))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'action' => 'super_admin.request_export',
        ]);
    }

    public function test_school_support_does_not_expose_metrics_before_intervention(): void
    {
        $school = School::factory()->create();
        Student::factory()->create(['school_id' => $school->id]);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);

        $this->actingAs($admin)
            ->get('/admin/school-support')
            ->assertOk()
            ->assertSee('Ouvrez un contexte temporaire avant de consulter les données de cette école.');
    }

    public function test_context_open_and_close_are_audited_and_update_the_session(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test(SchoolSupport::class)
            ->set('school', $school->id)
            ->call('openContext')
            ->assertRedirect(Dashboard::getUrl());

        $this->assertSame($school->id, (int) session('scolaris_intervention.school_id'));
        $this->assertGreaterThan(now()->timestamp, (int) session('scolaris_intervention.expires_at'));
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'action' => 'super_admin.context_opened',
        ]);

        Livewire::actingAs($admin)
            ->test(SchoolSupport::class)
            ->set('school', $school->id)
            ->call('leaveContext')
            ->assertRedirect(SchoolSupport::getUrl());

        $this->assertNull(session('scolaris_intervention.school_id'));
        $this->assertNull(session('scolaris_intervention.expires_at'));
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'action' => 'super_admin.context_closed',
        ]);
    }
}
