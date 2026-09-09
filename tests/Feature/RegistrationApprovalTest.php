<?php

namespace Tests\Feature;

use App\Models\RegistrationRequest;
use App\Models\School;
use App\Models\User;
use App\Services\RegistrationApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_request_is_created_and_approved_by_super_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Director',
            'email' => 'director-new@example.com',
            'requested_role' => 'director',
            'school_name' => 'New School',
            'password' => 'Secure-password-123!',
            'password_confirmation' => 'Secure-password-123!',
        ]);

        $response->assertRedirect();
        $request = RegistrationRequest::firstOrFail();
        $owner = User::factory()->create(['role' => 'super_admin', 'school_id' => null, 'email_verified_at' => now()]);

        $user = app(RegistrationApprovalService::class)->approve($request, $owner);

        $this->assertSame('director', $user->role);
        $this->assertTrue(Hash::check('Secure-password-123!', $user->password));
        $this->assertSame(RegistrationRequest::APPROVED, $request->fresh()->status);
        $this->assertNotNull($user->school_id);
    }

    public function test_non_director_request_requires_an_existing_school(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Teacher',
            'email' => 'teacher-new@example.com',
            'requested_role' => 'teacher',
            'password' => 'Secure-password-123!',
            'password_confirmation' => 'Secure-password-123!',
        ]);

        $response->assertRedirect('/register')->assertSessionHasErrors('school_id');
    }

    public function test_existing_school_request_requires_a_matching_school_code(): void
    {
        $school = School::factory()->create(['code' => 'ECOLE-001']);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Teacher',
            'email' => 'teacher-code@example.com',
            'requested_role' => 'teacher',
            'school_id' => $school->id,
            'school_code' => 'WRONG-CODE',
            'password' => 'Secure-password-123!',
            'password_confirmation' => 'Secure-password-123!',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('registration_requests', 0);
    }

    public function test_existing_school_request_accepts_code_case_insensitively(): void
    {
        $school = School::factory()->create(['code' => 'ECOLE-001']);

        $response = $this->post('/register', [
            'name' => 'Teacher',
            'email' => 'teacher-code-valid@example.com',
            'requested_role' => 'teacher',
            'school_id' => $school->id,
            'school_code' => 'ecole-001',
            'password' => 'Secure-password-123!',
            'password_confirmation' => 'Secure-password-123!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('registration_requests', [
            'email' => 'teacher-code-valid@example.com',
            'school_id' => $school->id,
            'school_code' => 'ecole-001',
        ]);
    }
}
