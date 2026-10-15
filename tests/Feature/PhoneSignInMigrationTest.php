<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PhoneSignInMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_sign_in_migration_rejects_phones_that_collide_after_normalization(): void
    {
        $migration = require database_path('migrations/2026_10_13_000001_enable_phone_sign_in.php');
        $migration->down();

        DB::table('users')->insert([
            $this->userRow('duplicate-one@example.com', '90 00 00 01'),
            $this->userRow('duplicate-two@example.com', '+22890000001'),
        ]);

        try {
            $migration->up();
            $this->fail('Expected the migration to reject normalized duplicate phone numbers.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('share a phone number after normalization', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('users', 'login_phone'));
    }

    public function test_phone_sign_in_migration_cannot_be_rolled_back_while_phone_only_users_exist(): void
    {
        User::factory()->create([
            'email' => null,
            'phone' => '+22890000001',
        ]);

        $migration = require database_path('migrations/2026_10_13_000001_enable_phone_sign_in.php');

        try {
            $migration->down();
            $this->fail('Expected rollback to reject phone-only user accounts.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot roll back phone sign-in while phone-only user accounts exist.', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('users', 'login_phone'));
        $this->assertSame(1, DB::table('users')->whereNull('email')->count());
    }

    private function userRow(string $email, string $phone): array
    {
        return [
            'name' => $email,
            'email' => $email,
            'phone' => $phone,
            'password' => 'hashed-password',
            'role' => 'teacher',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
