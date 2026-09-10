<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('report_card_templates')->nullOnDelete();
            $table->string('document_type')->default('certificate');
            $table->string('number');
            $table->json('data');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id','number']);
        });
    }
    public function down(): void { Schema::dropIfExists('certificates'); }
};
