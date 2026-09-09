<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['school_id', 'academic_year_id', 'active']);
        });

        Schema::create('fees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->boolean('mandatory')->default(true);
            $table->string('type')->default('tuition');
            $table->date('due_date')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['school_id', 'fee_structure_id', 'due_date']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->unsignedBigInteger('total_amount');
            $table->string('currency', 3)->default('XOF');
            $table->date('due_date');
            $table->string('status')->default('DRAFT');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'number']);
            $table->index(['school_id', 'student_id', 'status', 'due_date']);
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
            $table->index(['school_id', 'invoice_id']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('payment_method')->default('CASH');
            $table->string('status')->default('PENDING');
            $table->string('reference')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('unallocated_amount')->default(0);
            $table->timestamps();
            $table->index(['school_id', 'student_id', 'status', 'paid_at']);
            $table->unique(['school_id', 'reference']);
            $table->unique(['school_id', 'idempotency_key']);
        });

        Schema::create('payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();
            $table->unique(['payment_id', 'invoice_id']);
            $table->index(['school_id', 'invoice_id']);
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('provider_transaction_id')->nullable();
            $table->string('merchant_reference');
            $table->string('status')->default('PENDING');
            $table->unsignedInteger('attempts')->default(0);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_transaction_id']);
            $table->unique(['provider', 'merchant_reference']);
            $table->index(['school_id', 'status']);
        });

        Schema::create('receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('number');
            $table->unsignedBigInteger('balance_after');
            $table->timestamps();
            $table->unique(['school_id', 'number']);
        });

        Schema::create('collection_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->date('scheduled_period');
            $table->string('channel')->default('sms');
            $table->string('status')->default('PENDING');
            $table->json('provider_response')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'invoice_id', 'type', 'scheduled_period', 'channel'], 'collection_reminder_idempotency');
            $table->index(['school_id', 'status', 'scheduled_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_reminders');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('fee_structures');
    }
};
