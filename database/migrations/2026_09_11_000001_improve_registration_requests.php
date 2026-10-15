<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->string('request_type')->default('new_school')->after('id');
            $table->string('city')->nullable()->after('school_code');
            $table->text('message')->nullable()->after('phone');
            $table->string('preferred_channel')->nullable()->after('message');
            $table->string('email')->nullable()->change();
            $table->string('password_hash')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->dropColumn(['request_type', 'city', 'message', 'preferred_channel']);
            $table->string('email')->nullable(false)->change();
            $table->string('password_hash')->nullable(false)->change();
        });
    }
};
