<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff_invitations', function (Blueprint $table): void {
            $table->string('preferred_channel')->nullable()->after('secondary_role');
        });
    }

    public function down(): void
    {
        Schema::table('staff_invitations', function (Blueprint $table): void {
            $table->dropColumn('preferred_channel');
        });
    }
};
