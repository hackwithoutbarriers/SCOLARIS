<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
        });

        Schema::table('schools', function (Blueprint $table): void {
            $table->json('education_cycles')->nullable()->after('expense_categories');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('place_of_birth');
        });

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('education_cycles');
        });
    }
};
