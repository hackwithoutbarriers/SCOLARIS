<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserAccessLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_role_and_activation_changes_require_the_target_school_intervention_and_are_audited(): void
    {
        $school = School::factory()->create();
        $member = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'director',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);

        $this->actingAs($admin)
            ->get("/admin/users/{$member->id}/edit")
            ->assertForbidden();

        $this->withSession([
            'scolaris_intervention.school_id' => $school->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ]);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $member->getKey()])
            ->fillForm([
                'first_name' => 'Membre',
                'last_name' => 'École',
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'school_id' => $school->id,
                'role' => 'accountant',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('accountant', $member->fresh()->role);
        $this->assertFalse($member->fresh()->is_active);

        $audit = AuditLog::query()
            ->where('action', 'user.access_updated')
            ->where('auditable_id', $member->id)
            ->sole();

        $this->assertSame($school->id, $audit->school_id);
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame(['role' => 'director', 'is_active' => true], $audit->old_values);
        $this->assertSame(['role' => 'accountant', 'is_active' => false], $audit->new_values);
        $this->assertSame(['role', 'is_active'], array_keys($audit->new_values));
    }

    public function test_super_admin_cannot_edit_an_account_from_a_different_intervention_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $memberB = User::factory()->create(['school_id' => $schoolB->id]);
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);

        $this->withSession([
            'scolaris_intervention.school_id' => $schoolA->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ])
            ->actingAs($admin)
            ->get("/admin/users/{$memberB->id}/edit")
            ->assertNotFound();
    }
}
