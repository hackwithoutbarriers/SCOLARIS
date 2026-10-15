<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table): void {
            $table->string('status')->default('draft')->after('notes');
            $table->unsignedInteger('version')->default(1)->after('status');
            $table->foreignId('published_by')->nullable()->after('version')->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('published_by');
            $table->index(['school_id', 'academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table): void {
            $table->dropForeign(['published_by']);
            $table->dropIndex(['school_id', 'academic_year_id', 'status']);
            $table->dropColumn(['status', 'version', 'published_by', 'published_at']);
        });
    }
};
