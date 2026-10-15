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
        Schema::table('attestations', function (Blueprint $table) {
            $table->string('numero_serie')->nullable()->unique()->after('id');
        });

        DB::table('attestations')
            ->select(['id', 'numero_attestation'])
            ->orderBy('id')
            ->eachById(function (object $attestation): void {
                preg_match('/(\d+)$/', (string) $attestation->numero_attestation, $matches);

                DB::table('attestations')
                    ->where('id', $attestation->id)
                    ->update(['numero_serie' => $matches[1] ?? (string) $attestation->id]);
            });

        $nextId = max(
            2659,
            ((int) DB::table('attestations')->max('id')) + 1,
        );

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $sequence = DB::selectOne(
                "select pg_get_serial_sequence('attestations', 'id') as sequence"
            )->sequence ?? null;

            if ($sequence !== null) {
                DB::statement('select setval(?, ?, false)', [$sequence, $nextId]);
            }
        } elseif ($driver === 'sqlite') {
            DB::table('sqlite_sequence')->updateOrInsert(
                ['name' => 'attestations'],
                ['seq' => $nextId - 1],
            );
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('alter table attestations auto_increment = '.$nextId);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->dropUnique(['numero_serie']);
            $table->dropColumn('numero_serie');
        });
    }
};
