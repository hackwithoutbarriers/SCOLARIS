<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('report_cards', function (Blueprint $table): void {
            $table->string('mention_override')->nullable()->after('data');
            $table->text('mention_override_reason')->nullable()->after('mention_override');
            $table->foreignId('mention_override_by')->nullable()->after('mention_override_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('mention_override_at')->nullable()->after('mention_override_by');
        });
    }

    public function down(): void
    {
        Schema::table('report_cards', function (Blueprint $table): void {
            $table->dropForeign(['mention_override_by']);
            $table->dropColumn(['mention_override', 'mention_override_reason', 'mention_override_by', 'mention_override_at']);
        });
    }
};
