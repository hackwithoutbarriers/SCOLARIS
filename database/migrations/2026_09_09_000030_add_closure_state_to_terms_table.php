<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table): void {
            $table->string('status')->default('open')->after('sort_order');
            $table->timestamp('closed_at')->nullable()->after('status');
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->index(['school_id', 'academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table): void {
            $table->dropForeign(['closed_by']);
            $table->dropIndex(['school_id', 'academic_year_id', 'status']);
            $table->dropColumn(['status', 'closed_at', 'closed_by']);
        });
    }
};
