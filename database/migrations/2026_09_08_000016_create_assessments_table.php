<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_config_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->date('assessment_date')->nullable();
            $table->decimal('max_score', 8, 2)->default(100);
            $table->decimal('weight', 8, 2)->default(100);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['subject_config_id', 'term_id', 'title']);
            $table->index(['school_id', 'term_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('assessments'); }
};
