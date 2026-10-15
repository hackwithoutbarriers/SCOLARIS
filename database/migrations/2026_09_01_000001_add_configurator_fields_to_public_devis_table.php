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
        Schema::table('public_devis', function (Blueprint $table) {
            $table->string('categorie')->nullable()->after('description_besoin');
            $table->string('sous_type')->nullable()->after('categorie');
            $table->json('dimensions')->nullable()->after('sous_type');
            $table->string('finition')->nullable()->after('dimensions');
            $table->string('vitrage')->nullable()->after('finition');
            $table->json('options')->nullable()->after('vitrage');
            $table->unsignedInteger('estimation_min')->nullable()->after('options');
            $table->unsignedInteger('estimation_max')->nullable()->after('estimation_min');
            $table->string('devise')->default('FCFA')->after('estimation_max');
            $table->string('source')->default('simulateur')->after('devise');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_devis', function (Blueprint $table) {
            $table->dropColumn([
                'categorie',
                'sous_type',
                'dimensions',
                'finition',
                'vitrage',
                'options',
                'estimation_min',
                'estimation_max',
                'devise',
                'source',
            ]);
        });
    }
};
