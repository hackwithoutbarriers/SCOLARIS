<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\NotificationQueue;
use App\Models\School;
use App\Models\Student;
use App\Services\DueReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DueReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_configured_j3_reminder_is_whatsapp_and_idempotent(): void
    {
        $school = School::factory()->create(['due_reminder_days' => 3]);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id]);
        $student->guardians()->attach($guardian, ['receives_whatsapp' => true, 'receives_sms' => false]);
        $structure = FeeStructure::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Scolarité']);
        Fee::create(['school_id' => $school->id, 'fee_structure_id' => $structure->id, 'name' => 'Tranche', 'amount' => 50000, 'due_date' => today()->addDays(3)]);
        $invoice = $student->invoices()->first();
        if (!$invoice) {
            $invoice = Invoice::create([
                'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id,
                'fee_structure_id' => $structure->id, 'number' => 'INV-TEST', 'total_amount' => 50000,
                'due_date' => today()->addDays(3), 'status' => Invoice::ISSUED,
            ]);
        }

        $service = app(DueReminderService::class);
        $service->schedule(today());
        $service->schedule(today());

        $this->assertDatabaseHas('collection_reminders', ['invoice_id' => $invoice->id, 'type' => 'RAPPEL_ECHEANCE', 'channel' => 'whatsapp']);
        $this->assertSame(1, NotificationQueue::where('event', 'rappel_echeance')->count());
        $this->assertSame('whatsapp', NotificationQueue::where('event', 'rappel_echeance')->value('channel'));
    }
}
