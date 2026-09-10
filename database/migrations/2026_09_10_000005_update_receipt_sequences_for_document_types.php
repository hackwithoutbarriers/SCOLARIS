<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('receipt_sequences', function (Blueprint $table): void {
            $table->string('sequence_type')->default('receipt')->after('school_id');
            $table->dropUnique(['school_id']);
            $table->unique(['school_id', 'sequence_type']);
        });
    }

    public function down(): void
    {
        Schema::table('receipt_sequences', function (Blueprint $table): void {
            $table->dropUnique(['school_id', 'sequence_type']);
            $table->dropColumn('sequence_type');
            $table->unique('school_id');
        });
    }
};
