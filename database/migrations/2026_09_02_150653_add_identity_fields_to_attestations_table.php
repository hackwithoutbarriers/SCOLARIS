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
        Schema::table('attestations', function (Blueprint $table) {
            $table->string('photo_profil')->nullable()->after('apprenti_nom_prenom');
            $table->date('date_naissance')->nullable()->after('photo_profil');
            $table->string('lieu_naissance')->nullable()->after('date_naissance');
            $table->string('nationalite')->default('Togolaise')->after('lieu_naissance');
            $table->enum('type_document', ['certificat', 'attestation_travail'])->default('certificat')->after('nationalite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->dropColumn(['photo_profil', 'date_naissance', 'lieu_naissance', 'nationalite', 'type_document']);
        });
    }
};
