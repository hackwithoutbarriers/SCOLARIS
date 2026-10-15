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
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->string('numero_devis')->unique();
            $table->string('client_nom');
            $table->string('client_telephone');
            $table->string('client_ville')->nullable();
            $table->string('client_pays')->default('Togo');
            $table->text('description_chantier');
            $table->decimal('montant_materiel', 12, 2);
            $table->decimal('montant_main_doeuvre', 12, 2);
            $table->decimal('montant_total', 12, 2);
            $table->unsignedTinyInteger('acompte_requis_pourcentage')->default(0);
            $table->enum('statut', ['brouillon', 'envoye', 'accepte', 'livre', 'refuse'])->default('brouillon');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
