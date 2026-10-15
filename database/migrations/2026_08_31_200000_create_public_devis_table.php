<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('public_devis', function (Blueprint $table) {
            $table->id();
            $table->string('numero_demande')->unique();
            $table->string('nom');
            $table->string('telephone');
            $table->string('ville')->nullable();
            $table->string('pays')->default('Togo');
            $table->text('description_besoin');
            $table->enum('statut', ['nouvelle', 'en_cours', 'convertie'])->default('nouvelle');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_devis');
    }
};
