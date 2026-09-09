<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appreciations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('average_score', 5, 2);
            $table->unsignedInteger('rank')->nullable();
            $table->string('label')->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id', 'term_id']);
            $table->index(['school_id', 'academic_year_id', 'term_id', 'rank']);
        });
    }

    public function down(): void { Schema::dropIfExists('appreciations'); }
};
