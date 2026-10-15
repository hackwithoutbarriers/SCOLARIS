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
        Schema::create('temoignages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devis_id')->nullable()->constrained('devis')->nullOnDelete();
            $table->string('nom_client');
            $table->string('ville')->nullable();
            $table->string('projet_type')->nullable();
            $table->string('projet_ref')->nullable();
            $table->unsignedTinyInteger('note')->default(5);
            $table->text('texte');
            $table->string('photo_projet')->nullable();
            $table->date('date_projet')->nullable();
            $table->boolean('consentement')->default(false);
            $table->enum('statut', ['brouillon', 'en_attente', 'publie'])->default('en_attente');
            $table->enum('source', ['interne', 'google'])->default('interne');
            $table->timestamps();

            $table->index(['statut', 'consentement']);
            $table->index('projet_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temoignages');
    }
};
