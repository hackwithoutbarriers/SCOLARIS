<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_to_filament_panel(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'email' => 'admin@test.test', 'role' => 'admin']);
        $response = $this->actingAs($user)->get('/admin');
        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
