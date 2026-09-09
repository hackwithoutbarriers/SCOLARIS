<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name')->nullable();
            $table->string('relationship')->nullable();
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('secondary_phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('active')->default(true);
            $table->index(['school_id', 'phone']);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('guardians'); }
};
