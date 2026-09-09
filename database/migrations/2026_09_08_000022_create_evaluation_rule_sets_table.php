<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluation_rule_sets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->string('grading_system')->default('percentage');
            $table->decimal('maximum_score', 8, 2)->default(100);
            $table->string('rounding')->default('presentation_only');
            $table->string('average_method')->default('weighted_average');
            $table->json('rules')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'academic_year_id', 'class_room_id', 'version'], 'evaluation_rule_set_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_rule_sets');
    }
};
