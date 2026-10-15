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
        Schema::create('devis_public_references', function (Blueprint $table) {
            $table->id();
            $table->string('reference_publique', 5)->unique();
            $table->foreignId('devis_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('devis', function (Blueprint $table) {
            $table->string('reference_publique', 5)->nullable()->unique()->after('numero_devis');
        });

        DB::table('devis')->select('id')->orderBy('id')->each(function (object $devis): void {
            do {
                $reference = (string) random_int(10000, 99999);
            } while (DB::table('devis_public_references')->where('reference_publique', $reference)->exists());

            DB::table('devis_public_references')->insert([
                'reference_publique' => $reference,
                'devis_id' => $devis->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('devis')->where('id', $devis->id)->update([
                'reference_publique' => $reference,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->dropUnique(['reference_publique']);
            $table->dropColumn('reference_publique');
        });

        Schema::dropIfExists('devis_public_references');
    }
};
