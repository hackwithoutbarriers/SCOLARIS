<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'scolaris:create-super-admin
        {email? : Login email; defaults to SCOLARIS_OWNER_EMAIL}
        {--name= : Display name; defaults to SCOLARIS_OWNER_NAME}
        {--password= : Password; defaults to SCOLARIS_OWNER_PASSWORD}';

    protected $description = 'Create or update a Scolaris super administrator';

    public function handle(): int
    {
        $email = strtolower(trim((string) ($this->argument('email') ?: getenv('SCOLARIS_OWNER_EMAIL') ?: config('scolaris.owner.email'))));
        $name = trim((string) ($this->option('name') ?: getenv('SCOLARIS_OWNER_NAME') ?: config('scolaris.owner.name') ?: ''));
        $name = $name !== '' ? $name : ucfirst((string) str()->before($email, '@'));
        $password = (string) ($this->option('password') ?: getenv('SCOLARIS_OWNER_PASSWORD') ?: config('scolaris.owner.password'));

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::INVALID;
        }

        $user = \App\Models\User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'first_name' => $name,
                'last_name' => null,
                'school_id' => null,
                'role' => 'super_admin',
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->info("Super Admin ready: {$user->email}");

        return self::SUCCESS;
    }
}
