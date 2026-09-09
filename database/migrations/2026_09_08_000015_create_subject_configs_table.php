<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subject_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('grading_method')->default('weighted_average');
            $table->decimal('passing_score', 5, 2)->default(50);
            $table->decimal('max_score', 8, 2)->default(100);
            $table->decimal('weight', 8, 2)->default(100);
            $table->json('rules')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'subject_id', 'academic_year_id', 'class_room_id', 'version'], 'subject_config_version_unique');
            $table->index(['school_id', 'academic_year_id', 'subject_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('subject_configs'); }
};
