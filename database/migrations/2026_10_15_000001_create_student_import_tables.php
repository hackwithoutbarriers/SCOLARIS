<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_room_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->char('file_sha256', 64);
            $table->string('file_path')->nullable();
            $table->string('status', 24)->default('uploaded');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('ignored_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->json('mapping_json')->nullable();
            $table->char('mapping_fingerprint', 64)->nullable();
            $table->string('delimiter', 4)->nullable();
            $table->unsignedInteger('header_row')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status', 'created_at']);
            $table->index(['school_id', 'file_sha256']);
        });

        Schema::create('student_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_import_batch_id')->constrained('student_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('status', 24)->default('pending');
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('candidate_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('decision', 16)->nullable();
            $table->json('normalized_data')->nullable();
            $table->json('issues_json')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'student_import_batch_id', 'row_number'], 'student_import_rows_batch_row_unique');
            $table->index(['student_import_batch_id', 'status']);
        });

        Schema::create('student_import_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('fingerprint', 64);
            $table->json('mapping_json');
            $table->timestamps();
            $table->unique(['school_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_import_mappings');
        Schema::dropIfExists('student_import_rows');
        Schema::dropIfExists('student_import_batches');
    }
};
