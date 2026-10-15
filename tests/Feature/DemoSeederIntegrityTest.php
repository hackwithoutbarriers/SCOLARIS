<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_creates_two_schools_and_all_core_roles(): void
    {
        $this->artisan('db:seed')->assertExitCode(0);

        $this->assertGreaterThanOrEqual(2, School::query()->count());
        $this->assertDatabaseHas('users', ['email' => 'director@example.com', 'role' => 'director']);
        $this->assertDatabaseHas('users', ['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->assertDatabaseHas('users', ['email' => 'accountant@example.com', 'role' => 'accountant']);
        $this->assertDatabaseHas('users', ['email' => 'censeur@example.com', 'role' => 'censeur']);
        $this->assertDatabaseHas('users', ['email' => 'secretary@example.com', 'role' => 'secretary']);
        $this->assertDatabaseHas('users', ['email' => 'superadmin@example.com', 'role' => 'super_admin', 'school_id' => null]);

        $this->assertTrue(User::query()->where('role', 'super_admin')->where('is_active', true)->exists());
        $this->assertTrue(User::query()->where('role', 'censeur')->where('is_active', true)->exists());
    }
}
