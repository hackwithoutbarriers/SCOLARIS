<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = config('admin.email');
        $adminPassword = config('admin.password');

        if (is_string($adminEmail) && $adminEmail !== '' && is_string($adminPassword) && $adminPassword !== '') {
            User::firstOrCreate(['email' => $adminEmail], [
                'name' => config('admin.name'),
                'password' => $adminPassword,
                'status' => User::STATUS_APPROVED,
                'is_superuser' => true,
                'approved_at' => now(),
            ]);
        }

        $this->call([
            ProjetSeeder::class,
            DevisSeeder::class,
            AttestationSeeder::class,
            TemoignageSeeder::class,
        ]);
    }
}
