<?php

use App\Services\PhoneNumberFormatter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $normalizedPhones = [];
        foreach (DB::table('users')->whereNotNull('phone')->orderBy('id')->get(['id', 'phone']) as $user) {
            $normalized = PhoneNumberFormatter::toE164($user->phone);
            if (! $normalized) {
                continue;
            }

            if (isset($normalizedPhones[$normalized])) {
                throw new RuntimeException(sprintf(
                    'Users %d and %d share a phone number after normalization; resolve the duplicate before enabling phone sign-in.',
                    $normalizedPhones[$normalized],
                    $user->id,
                ));
            }

            $normalizedPhones[$normalized] = $user->id;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->string('login_phone')->nullable()->unique();
        });

        foreach ($normalizedPhones as $phone => $userId) {
            DB::table('users')->where('id', $userId)->update(['login_phone' => $phone]);
        }
    }

    public function down(): void
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException('Cannot roll back phone sign-in while phone-only user accounts exist.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_login_phone_unique');
            $table->dropColumn('login_phone');
            $table->string('email')->nullable(false)->change();
        });
    }
};
