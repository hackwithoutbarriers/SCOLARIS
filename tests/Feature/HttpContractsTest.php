<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpContractsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_requests_return_json_401(): void
    {
        $this->getJson('/api/attendance/sessions')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_inactive_users_are_rejected_by_api_middleware(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'is_active' => false]);

        $this->actingAs($user)->getJson('/api/attendance/sessions')->assertForbidden();
    }

    public function test_api_validation_not_found_and_method_contracts_are_json(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        $this->actingAs($user)->postJson('/api/payments', [])
            ->assertStatus(400)
            ->assertJsonStructure(['message']);
        $this->actingAs($user)->getJson('/api/payments')
            ->assertStatus(405)
            ->assertJsonStructure(['message']);
        $this->actingAs($user)->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_payment_validation_returns_422_after_idempotency_is_supplied(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);

        $this->actingAs($user)
            ->withHeader('Idempotency-Key', 'contract-test')
            ->postJson('/api/payments', ['student_id' => 999999, 'amount' => 0, 'payment_method' => 'INVALID'])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_api_rate_limit_returns_429(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $this->actingAs($user);

        for ($attempt = 0; $attempt < 120; $attempt++) {
            $this->getJson('/api/attendance/sessions')->assertOk();
        }

        $this->getJson('/api/attendance/sessions')->assertTooManyRequests();
    }
}
