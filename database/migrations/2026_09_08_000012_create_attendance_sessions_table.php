<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('session_date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->string('status')->default('DRAFT');
            $table->uuid('sync_id')->unique();
            $table->timestamps();
            $table->unique(['school_id', 'class_room_id', 'session_date']);
            $table->index(['school_id', 'session_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('attendance_sessions'); }
};
