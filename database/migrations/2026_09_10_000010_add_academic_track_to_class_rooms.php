<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('class_rooms', function (Blueprint $table): void {
            $table->string('cycle')->nullable()->after('grade_level');
            $table->string('filiere')->nullable()->after('cycle');
            $table->index(['school_id', 'academic_year_id', 'cycle', 'filiere']);
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table): void {
            $table->dropIndex(['school_id', 'academic_year_id', 'cycle', 'filiere']);
            $table->dropColumn(['cycle', 'filiere']);
        });
    }
};
