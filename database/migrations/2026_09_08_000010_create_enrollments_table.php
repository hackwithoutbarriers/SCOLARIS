<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->date('enrollment_date');
            $table->date('enrolled_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'academic_year_id', 'class_room_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('enrollments'); }
};
