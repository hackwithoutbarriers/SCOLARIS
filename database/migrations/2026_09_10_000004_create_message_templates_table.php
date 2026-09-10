<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['school_id', 'code', 'is_active']);
        });
        DB::table('message_templates')->insert([
            ['code' => 'absence_notification', 'channel' => 'both', 'subject' => 'Absence signalée', 'body' => 'Bonjour {{guardian_name}}, l’élève {{student_name}} a été marqué absent le {{date}} dans la classe {{class_name}}. — {{school_name}}', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'late_notification', 'channel' => 'both', 'subject' => 'Retard signalé', 'body' => 'Bonjour {{guardian_name}}, l’élève {{student_name}} est arrivé en retard le {{date}} dans la classe {{class_name}}. — {{school_name}}', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'payment_reminder', 'channel' => 'both', 'subject' => 'Rappel de paiement', 'body' => 'Bonjour {{guardian_name}}, le solde de {{amount}} reste à régler pour {{student_name}}. — {{school_name}}', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'report_card_ready', 'channel' => 'both', 'subject' => 'Bulletin disponible', 'body' => 'Bonjour {{guardian_name}}, le bulletin de {{student_name}} est disponible. Consultez-le ici : {{report_card_url}} — {{school_name}}', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
