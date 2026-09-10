<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRoleActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_worklists_are_available_and_dashboard_does_not_link_to_json_endpoints(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        $this->actingAs($director)->get('/admin/absence-worklist')->assertOk()->assertSee('Absences à traiter');
        $this->actingAs($director)->get('/admin/collection-center')->assertOk()->assertSee('Centre de recouvrement');
        $this->actingAs($director)->get('/admin/missing-grades')->assertOk()->assertSee('Notes à compléter');

        $dashboard = $this->actingAs($director)->get('/admin');
        $dashboard->assertOk()->assertDontSee('/api/attendance/history')->assertDontSee('/api/payments/debtors');
    }

    public function test_accountant_can_use_collection_center_but_not_academic_worklists(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);

        $this->actingAs($accountant)->get('/admin/collection-center')->assertOk();
        $this->actingAs($accountant)->get('/admin/missing-grades')->assertForbidden();
        $this->actingAs($accountant)->get('/admin/absence-worklist')->assertForbidden();
    }

    public function test_teacher_sees_only_their_missing_grades_worklist(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        $this->actingAs($teacher)->get('/admin/missing-grades')->assertOk();
        $this->actingAs($teacher)->get('/admin/collection-center')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/absence-worklist')->assertForbidden();
    }
}
