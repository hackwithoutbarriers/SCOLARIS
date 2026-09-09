<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('notification_queue', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('sms');
            $table->string('provider')->default('mock');
            $table->string('recipient')->nullable();
            $table->string('template')->nullable();
            $table->string('event');
            $table->json('payload');
            $table->string('status')->default('PENDING');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status', 'scheduled_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('notification_queue'); }
};
