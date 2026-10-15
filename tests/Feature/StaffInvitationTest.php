<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login as LoginPage;
use App\Filament\Resources\UserResource;
use App\Models\NotificationQueue;
use App\Models\School;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\StaffInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StaffInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_invite_staff_with_two_roles_and_token_is_single_use(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        [$invitation, $token] = app(StaffInvitationService::class)->create($director, [
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'phone' => '+22890000001',
            'role' => 'teacher',
            'secondary_role' => 'secretary',
        ]);

        $this->post("/invitations/{$token}", [
            'password' => 'Strong-pass-123',
            'password_confirmation' => 'Strong-pass-123',
        ])->assertRedirect('/admin/login');

        $user = User::withoutGlobalScopes()->where('email', 'staff@example.com')->firstOrFail();
        $this->assertSame($school->id, $user->school_id);
        $this->assertSame('teacher', $user->role);
        $this->assertSame('secretary', $user->secondary_role);
        $this->assertTrue($user->isSecretary());
        $this->assertTrue(Hash::check('Strong-pass-123', $user->password));

        $this->post("/invitations/{$token}", [
            'password' => 'Another-pass-123',
            'password_confirmation' => 'Another-pass-123',
        ])->assertSessionHasErrors('token');
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_director_cannot_invite_into_another_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $director = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'director']);

        [$invitation] = app(StaffInvitationService::class)->create($director, [
            'name' => 'Staff Member',
            'email' => 'staff-b@example.com',
            'phone' => '+22890000002',
            'school_id' => $schoolB->id,
            'role' => 'accountant',
        ]);

        $this->assertSame($schoolA->id, $invitation->school_id);
    }

    public function test_accepting_invitation_cannot_move_existing_account_between_schools(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $existingUser = User::factory()->create([
            'school_id' => $schoolA->id,
            'email' => 'existing-staff@example.com',
            'role' => 'teacher',
        ]);
        $director = User::factory()->create(['school_id' => $schoolB->id, 'role' => 'director']);

        [$invitation, $token] = app(StaffInvitationService::class)->create($director, [
            'name' => 'Existing Staff',
            'email' => $existingUser->email,
            'role' => 'accountant',
        ]);

        $this->post("/invitations/{$token}", [
            'password' => 'Strong-pass-123',
            'password_confirmation' => 'Strong-pass-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame($schoolA->id, $existingUser->fresh()->school_id);
        $this->assertSame('teacher', $existingUser->fresh()->role);
        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_phone_only_invitation_creates_an_account_that_can_sign_in_by_phone(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        [$invitation, $token] = app(StaffInvitationService::class)->create($director, [
            'name' => 'Téléphone uniquement',
            'phone' => '90 00 00 01',
            'role' => 'teacher',
        ]);

        $this->post("/invitations/{$token}", [
            'password' => 'Strong-pass-123',
            'password_confirmation' => 'Strong-pass-123',
        ])->assertRedirect('/admin/login');

        $user = User::withoutGlobalScopes()->where('login_phone', '+22890000001')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertSame('+22890000001', $user->phone);
        $this->assertNotNull($invitation->fresh()->accepted_at);

        Livewire::test(LoginPage::class)
            ->set('data.identifier', '90 00 00 01')
            ->set('data.password', 'Strong-pass-123')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_email_login_remains_supported(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'email-login@example.com',
            'password' => 'Email-pass-123',
        ]);

        Livewire::test(LoginPage::class)
            ->set('data.identifier', 'EMAIL-LOGIN@example.com')
            ->set('data.password', 'Email-pass-123')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_channel_sends_invitation_through_notification_queue(): void
    {
        Mail::fake();
        config([
            'attendance.notifications.default_channel' => 'email',
            'attendance.notifications.provider' => 'mock',
        ]);

        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        [$invitation] = app(StaffInvitationService::class)->create($director, [
            'name' => 'Email Staff',
            'email' => 'email-staff@example.com',
            'role' => 'teacher',
        ]);

        $notification = NotificationQueue::query()
            ->where('event', 'invitation')
            ->where('recipient', 'email-staff@example.com')
            ->firstOrFail();

        app(NotificationService::class)->dispatchPending();

        Mail::assertOutgoingCount(1);
        $this->assertSame($invitation->id, StaffInvitation::query()->firstOrFail()->id);
        $this->assertSame('SENT', $notification->fresh()->status);
    }

    public function test_super_admin_can_invite_a_school_director_using_whatsapp_only(): void
    {
        config(['attendance.notifications.default_channel' => 'whatsapp']);
        $school = School::factory()->create();
        $owner = User::factory()->create(['role' => 'super_admin', 'school_id' => null]);

        $this->withSession([
            'scolaris_intervention.school_id' => $school->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ])->actingAs($owner);
        [$invitation] = app(StaffInvitationService::class)->create($owner, [
            'school_id' => $school->id,
            'name' => 'Directeur de test',
            'phone' => '+22890000000',
            'role' => 'director',
        ]);

        $this->assertSame('director', $invitation->role);
        $this->assertNull($invitation->email);
        $this->assertDatabaseHas('notification_queue', ['school_id' => $school->id, 'channel' => 'whatsapp']);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'user_id' => $owner->id,
            'action' => 'super_admin.staff_invitation_created',
        ]);
    }

    public function test_super_admin_cannot_invite_staff_without_matching_school_intervention(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $owner = User::factory()->create(['role' => 'super_admin', 'school_id' => null]);

        $this->actingAs($owner);
        try {
            app(StaffInvitationService::class)->create($owner, [
                'school_id' => $schoolA->id,
                'name' => 'Personnel A',
                'email' => 'staff-a@example.com',
                'role' => 'teacher',
            ]);
            $this->fail('An intervention is required to invite staff.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->withSession([
            'scolaris_intervention.school_id' => $schoolA->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ])->actingAs($owner);
        try {
            app(StaffInvitationService::class)->create($owner, [
                'school_id' => $schoolB->id,
                'name' => 'Personnel B',
                'email' => 'staff-b@example.com',
                'role' => 'teacher',
            ]);
            $this->fail('The invitation must stay in the active school.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('staff_invitations', 0);
        $this->assertDatabaseCount('notification_queue', 0);
    }

    public function test_intervention_user_form_cannot_create_super_admin_or_select_another_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $owner = User::factory()->create(['role' => 'super_admin', 'school_id' => null]);
        $this->withSession([
            'scolaris_intervention.school_id' => $schoolA->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ])->actingAs($owner);

        $this->get(UserResource::getUrl('create'))
            ->assertOk()
            ->assertDontSee('super_admin')
            ->assertDontSee($schoolB->name);
    }
}
