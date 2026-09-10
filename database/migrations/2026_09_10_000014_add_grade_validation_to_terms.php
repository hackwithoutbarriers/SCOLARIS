<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table): void {
            $table->string('grade_validation_status')->default('pending')->after('status');
            $table->timestamp('grades_validated_at')->nullable()->after('closed_by');
            $table->foreignId('grades_validated_by')->nullable()->after('grades_validated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table): void {
            $table->dropForeign(['grades_validated_by']);
            $table->dropColumn(['grade_validation_status', 'grades_validated_at', 'grades_validated_by']);
        });
    }
};
