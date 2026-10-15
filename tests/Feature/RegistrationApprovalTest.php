<?php

namespace Tests\Feature;

use App\Models\RegistrationRequest;
use App\Models\School;
use App\Models\User;
use App\Services\RegistrationApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

        $this->actingAs($owner);
        $user = app(RegistrationApprovalService::class)->approve($request, $owner);

        $this->assertSame('director', $user->role);
        $this->assertTrue(Hash::check('Secure-password-123!', $user->password));
        $this->assertSame(RegistrationRequest::APPROVED, $request->fresh()->status);
        $this->assertNotNull($user->school_id);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $user->school_id,
            'user_id' => $owner->id,
            'action' => 'super_admin.platform_director_account_created',
        ]);
    }

    public function test_public_registration_does_not_accept_staff_roles(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Teacher',
            'email' => 'teacher-new@example.com',
            'requested_role' => 'teacher',
            'password' => 'Secure-password-123!',
            'password_confirmation' => 'Secure-password-123!',
        ]);

        $response->assertRedirect('/register')->assertSessionHasErrors('requested_role');
    }

    public function test_public_registration_does_not_accept_school_selection(): void
    {
        $school = School::factory()->create(['code' => 'ECOLE-001']);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Teacher',
            'email' => 'teacher-code@example.com',
            'requested_role' => 'director',
            'school_id' => $school->id,
            'school_name' => 'New School',
            'password' => 'Secure-password-123!',
            'password_confirmation' => 'Secure-password-123!',
        ]);

        $response->assertRedirect('/register')->assertSessionHasErrors('school_id');
        $this->assertDatabaseCount('registration_requests', 0);
    }

    public function test_director_registration_does_not_require_an_existing_school_code(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Director',
            'email' => 'director-code-valid@example.com',
            'requested_role' => 'director',
            'school_name' => 'New School',
            'password' => 'Secure-password-123!',
            'password_confirmation' => 'Secure-password-123!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('registration_requests', [
            'email' => 'director-code-valid@example.com',
            'requested_role' => 'director',
            'school_name' => 'New School',
        ]);
    }

    public function test_public_request_accepts_phone_without_email_and_approval_sends_secure_invitation(): void
    {
        config(['attendance.notifications.default_channel' => 'whatsapp']);
        $response = $this->post('/register', [
            'request_type' => 'new_school',
            'name' => 'Directrice WhatsApp',
            'phone' => '+22890000001',
            'requested_role' => 'director',
            'school_name' => 'École WhatsApp',
            'password' => 'Secure-password-123!',
            'password_confirmation' => 'Secure-password-123!',
        ]);

        $response->assertRedirect();
        $request = RegistrationRequest::latest('id')->firstOrFail();
        $owner = User::factory()->create(['role' => 'super_admin', 'school_id' => null, 'email_verified_at' => now()]);

        $this->actingAs($owner);
        app(RegistrationApprovalService::class)->approve($request, $owner);

        $this->assertDatabaseHas('staff_invitations', [
            'school_id' => $request->fresh()->school_id,
            'phone' => '+22890000001',
            'email' => null,
            'role' => 'director',
        ]);
        $this->assertDatabaseHas('notification_queue', ['channel' => 'whatsapp']);
    }

    public function test_public_request_requires_at_least_one_contact_channel(): void
    {
        $this->from('/register')->post('/register', [
            'request_type' => 'new_school',
            'name' => 'Sans contact',
            'requested_role' => 'director',
            'school_name' => 'École sans contact',
        ])->assertRedirect('/register')->assertSessionHasErrors(['email', 'phone']);
    }

    public function test_super_admin_must_open_the_target_school_intervention_to_approve_existing_school_request(): void
    {
        $school = School::factory()->create(['code' => 'SCHOOL-EXISTING']);
        $request = RegistrationRequest::create([
            'request_type' => 'existing_school',
            'school_id' => $school->id,
            'school_name' => $school->name,
            'school_code' => $school->code,
            'name' => 'Personnel demandé',
            'email' => 'staff-request@example.com',
            'requested_role' => 'teacher',
            'password_hash' => Hash::make('Secure-password-123!'),
            'status' => RegistrationRequest::PENDING,
        ]);
        $owner = User::factory()->create(['role' => 'super_admin', 'school_id' => null]);

        $this->actingAs($owner);
        try {
            app(RegistrationApprovalService::class)->approve($request, $owner);
            $this->fail('A school intervention is required to approve an existing-school request.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(RegistrationRequest::PENDING, $request->fresh()->status);
        $this->withSession([
            'scolaris_intervention.school_id' => $school->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ]);

        $user = app(RegistrationApprovalService::class)->approve($request->fresh(), $owner);

        $this->assertSame($school->id, $user->school_id);
        $this->assertSame(RegistrationRequest::APPROVED, $request->fresh()->status);
    }
}
