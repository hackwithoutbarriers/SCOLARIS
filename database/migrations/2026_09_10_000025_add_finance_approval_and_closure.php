<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->unsignedBigInteger('expense_approval_threshold')->default(100000)->after('expense_categories');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->string('approval_status')->default('approved')->after('justification_path');
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_reason')->nullable()->after('approved_at');
            $table->index(['school_id', 'approval_status']);
        });

        Schema::create('cash_closures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->date('closure_date');
            $table->unsignedBigInteger('theoretical_balance');
            $table->unsignedBigInteger('physical_balance');
            $table->bigInteger('variance')->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('counted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'closure_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_closures');
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['school_id', 'approval_status']);
            $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'approval_reason']);
        });
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('expense_approval_threshold'));
    }
};
