<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_card_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('DRAFT');
            $table->string('orientation')->default('portrait');
            $table->json('schema');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'name', 'version']);
        });
    }

    public function down(): void { Schema::dropIfExists('report_card_templates'); }
};
