<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->unsignedBigInteger('amount');
            $table->text('description');
            $table->date('expense_date');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('voucher_number');
            $table->string('justification_path')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'voucher_number']);
            $table->index(['school_id', 'expense_date']);
        });

        Schema::create('expense_reversals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'expense_id']);
        });

        Schema::create('cash_opening_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->date('balance_date');
            $table->unsignedBigInteger('amount');
            $table->text('reason')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'balance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_opening_balances');
        Schema::dropIfExists('expense_reversals');
        Schema::dropIfExists('expenses');
    }
};
