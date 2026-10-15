<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->json('lignes_facturation')->nullable()->after('description_chantier');

            DB::table('devis')->select('id', 'montant_materiel')->orderBy('id')->each(function (object $devis): void {
                DB::table('devis')->where('id', $devis->id)->update([
                    'lignes_facturation' => json_encode([
                        [
                            'designation' => 'Matériel / fournitures',
                            'quantite' => 1,
                            'prix_unitaire' => (float) $devis->montant_materiel,
                            'total' => (float) $devis->montant_materiel,
                        ],
                    ], JSON_THROW_ON_ERROR),
                ]);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->dropColumn('lignes_facturation');
        });
    }
};
