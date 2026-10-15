<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_owner_is_verified_and_can_access_panel(): void
    {
        $this->artisan('scolaris:create-super-admin', [
            'email' => 'owner@example.com',
            '--name' => 'SaaS Owner',
            '--password' => 'Secure-password-123!',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $owner = User::withoutGlobalScopes()->where('email', 'owner@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('Secure-password-123!', $owner->password));
        $this->assertNotNull($owner->email_verified_at);
        $this->assertTrue($owner->isSuperAdmin());
        $this->assertTrue($owner->is_active);
    }

    public function test_bootstrap_command_is_safe_to_run_again_without_changing_the_account(): void
    {
        $this->artisan('scolaris:create-super-admin', [
            'email' => 'owner@example.com',
            '--name' => 'SaaS Owner',
            '--password' => 'Secure-password-123!',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $owner = User::withoutGlobalScopes()->where('email', 'owner@example.com')->firstOrFail();
        $originalHash = $owner->password;

        $this->artisan('scolaris:create-super-admin', [
            'email' => 'owner@example.com',
            '--name' => 'Changed Name',
            '--password' => 'Another-password-456!',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $this->assertSame($originalHash, $owner->fresh()->password);
        $this->assertSame('SaaS Owner', $owner->fresh()->name);
    }
}
