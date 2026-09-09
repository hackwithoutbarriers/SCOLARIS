<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Togo');
            $table->string('logo_path')->nullable();
            $table->boolean('active')->default(true);
            $table->string('timezone')->default('Africa/Lome');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('schools'); }
};
