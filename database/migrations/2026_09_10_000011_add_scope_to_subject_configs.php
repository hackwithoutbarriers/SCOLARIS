<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subject_configs', function (Blueprint $table): void {
            $table->string('cycle')->nullable()->after('class_room_id');
            $table->string('filiere')->nullable()->after('cycle');
            $table->index(['school_id', 'academic_year_id', 'cycle', 'filiere'], 'subject_configs_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('subject_configs', function (Blueprint $table): void {
            $table->dropIndex('subject_configs_scope_index');
            $table->dropColumn(['cycle', 'filiere']);
        });
    }
};
