<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:provision-admin')]
#[Description('Create or update the configured administrator account')]
class ProvisionAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->error('ADMIN_EMAIL et ADMIN_PASSWORD doivent être configurés.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = config('admin.name', 'Administrateur TRENOU');
        $user->status = User::STATUS_APPROVED;
        $user->is_superuser = true;
        $user->approved_at = now();

        if (! $user->exists || (bool) config('admin.force_password_reset', false)) {
            $user->password = $password;
        }

        $user->save();

        $this->info('Compte administrateur disponible pour '.$email.'.');

        return self::SUCCESS;
    }
}
