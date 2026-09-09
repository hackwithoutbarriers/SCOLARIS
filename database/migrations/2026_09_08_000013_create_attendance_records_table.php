<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('PRESENT');
            $table->unsignedInteger('late_minutes')->nullable();
            $table->timestamp('marked_at')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->string('device_id')->nullable();
            $table->uuid('client_operation_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['attendance_session_id', 'student_id']);
            $table->unique(['school_id', 'client_operation_id']);
            $table->index(['school_id', 'student_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('attendance_records'); }
};
