<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table): void {
            $table->uuid('client_operation_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table): void {
            $table->dropUnique(['client_operation_id']);
            $table->dropColumn('client_operation_id');
        });
    }
};
