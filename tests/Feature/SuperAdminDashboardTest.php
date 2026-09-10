<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_receives_a_multi_school_dashboard_and_support_center(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'school_id' => null,
        ]);
        School::factory()->create(['name' => 'École A']);
        School::factory()->create(['name' => 'École B']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Classes du jour')
            ->assertDontSee('/api/attendance/history');

        $this->actingAs($admin)
            ->get('/admin/school-support')
            ->assertOk()
            ->assertSee('Intervenir pour une école');
    }

    public function test_school_support_is_not_available_to_school_users(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        $this->actingAs($director)->get('/admin/school-support')->assertForbidden();
    }
}
