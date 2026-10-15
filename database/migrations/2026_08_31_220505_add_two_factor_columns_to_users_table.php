<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('password');
            $table->boolean('is_superuser')->default(false)->after('status');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('is_superuser');
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->text('two_factor_secret')->nullable()->after('approved_at');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn(['status', 'is_superuser', 'approved_by_user_id', 'approved_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
