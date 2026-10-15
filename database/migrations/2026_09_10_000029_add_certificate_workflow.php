<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->string('number')->nullable()->change();
            $table->string('status')->default('draft')->after('number');
            $table->foreignId('requested_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->string('delivery_method')->nullable()->after('validated_at');
            $table->string('delivered_to')->nullable()->after('delivery_method');
            $table->foreignId('delivered_by')->nullable()->after('delivered_to')->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable()->after('delivered_by');
            $table->text('workflow_note')->nullable()->after('delivered_at');
            $table->text('cancellation_reason')->nullable()->after('workflow_note');
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['validated_by']);
            $table->dropForeign(['delivered_by']);
            $table->dropIndex(['school_id', 'status']);
            $table->dropColumn(['status', 'requested_by', 'validated_by', 'validated_at', 'delivery_method', 'delivered_to', 'delivered_by', 'delivered_at', 'workflow_note', 'cancellation_reason']);
            $table->string('number')->nullable(false)->change();
        });
    }
};
