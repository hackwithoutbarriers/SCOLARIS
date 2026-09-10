<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->json('expense_categories')->nullable()->after('single_operator_mode');
        });
    }

    public function down(): void
    {
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('expense_categories'));
    }
};
