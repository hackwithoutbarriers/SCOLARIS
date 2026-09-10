<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mention_thresholds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('minimum_score', 5, 2);
            $table->decimal('maximum_score', 5, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['school_id', 'academic_year_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mention_thresholds');
    }
};
