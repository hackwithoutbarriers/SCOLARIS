<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->unsignedTinyInteger('due_reminder_days')->default(3)->after('active');
            $table->boolean('due_reminders_enabled')->default(true)->after('due_reminder_days');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn(['due_reminder_days', 'due_reminders_enabled']);
        });
    }
};
