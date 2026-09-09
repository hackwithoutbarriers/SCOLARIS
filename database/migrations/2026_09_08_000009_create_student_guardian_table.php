<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->boolean('receives_sms')->default(true);
            $table->boolean('receives_whatsapp')->default(false);
            $table->timestamps();
            $table->primary(['student_id', 'guardian_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('guardian_student'); }
};
