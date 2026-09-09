<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Services\Payments\InvoiceService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\CollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_confirmed_and_pending_payments_update_balance_only_when_confirmed(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $structure = FeeStructure::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Scolarite']);
        Fee::create(['school_id' => $school->id, 'fee_structure_id' => $structure->id, 'name' => 'Tranche', 'amount' => 100000, 'type' => 'tuition']);
        $invoice = app(InvoiceService::class)->generateForStudent($structure, $student);

        $payment = app(PaymentService::class)->recordManual(['school_id' => $school->id, 'student_id' => $student->id, 'amount' => 40000, 'payment_method' => 'CASH']);
        $this->assertSame(Invoice::PARTIALLY_PAID, $invoice->fresh()->status);
        $this->assertSame(60000, $invoice->fresh()->balance());
        $this->assertNotNull($payment->receipt);

        Payment::create(['school_id' => $school->id, 'student_id' => $student->id, 'amount' => 50000, 'payment_method' => 'CASH', 'status' => Payment::PENDING]);
        $this->assertSame(60000, $invoice->fresh()->balance());
    }

    public function test_overpayment_is_retained_as_credit_and_confirmation_is_idempotent(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $structure = FeeStructure::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Inscription']);
        Fee::create(['school_id' => $school->id, 'fee_structure_id' => $structure->id, 'name' => 'Inscription', 'amount' => 20000, 'type' => 'registration']);
        $invoice = app(InvoiceService::class)->generateForStudent($structure, $student);
        $payment = app(PaymentService::class)->recordManual(['school_id' => $school->id, 'student_id' => $student->id, 'amount' => 30000, 'payment_method' => 'TMONEY', 'reference' => 'TM-1']);
        app(PaymentService::class)->confirm($payment);
        $this->assertSame(10000, $payment->fresh()->unallocated_amount);
        $this->assertSame(1, $payment->fresh()->allocations()->count());
        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
    }

    public function test_exact_and_successive_payments_recalculate_balance_and_send_one_confirmation(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id]);
        $student->guardians()->attach($guardian->id, ['receives_sms' => true, 'is_primary' => true]);
        $structure = FeeStructure::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Tranches']);
        Fee::create(['school_id' => $school->id, 'fee_structure_id' => $structure->id, 'name' => 'Tranche 1', 'amount' => 100000, 'type' => 'tuition']);
        $invoice = app(InvoiceService::class)->generateForStudent($structure, $student);

        app(PaymentService::class)->recordManual(['school_id' => $school->id, 'student_id' => $student->id, 'amount' => 25000, 'payment_method' => 'CASH', 'idempotency_key' => 'p1']);
        app(PaymentService::class)->recordManual(['school_id' => $school->id, 'student_id' => $student->id, 'amount' => 25000, 'payment_method' => 'CASH', 'idempotency_key' => 'p2']);
        $this->assertSame(50000, $invoice->fresh()->balance());
        $this->assertSame(2, \App\Models\NotificationQueue::query()->where('event', 'payment_received')->count());

        app(PaymentService::class)->recordManual(['school_id' => $school->id, 'student_id' => $student->id, 'amount' => 50000, 'payment_method' => 'BANK', 'idempotency_key' => 'p3']);
        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->balance());
    }

    public function test_reversal_preserves_original_payment_and_reopens_balance(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $structure = FeeStructure::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Reversal']);
        Fee::create(['school_id' => $school->id, 'fee_structure_id' => $structure->id, 'name' => 'Fee', 'amount' => 100000, 'type' => 'tuition']);
        $invoice = app(InvoiceService::class)->generateForStudent($structure, $student);
        $payment = app(PaymentService::class)->recordManual(['school_id' => $school->id, 'student_id' => $student->id, 'amount' => 100000, 'payment_method' => 'CASH', 'idempotency_key' => 'reversal']);
        app(PaymentService::class)->reverse($payment, 40000, 'Correction caisse');
        $this->assertDatabaseHas('payment_reversals', ['payment_id' => $payment->id, 'amount' => 40000]);
        $this->assertSame(40000, $invoice->fresh()->balance());
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => \App\Models\PaymentReversal::class, 'action' => 'created']);
    }

    public function test_collection_reminders_are_scheduled_and_idempotent(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id]);
        $student->guardians()->attach($guardian->id, ['receives_sms' => true, 'is_primary' => true]);
        $structure = FeeStructure::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Recouvrement']);
        Fee::create(['school_id' => $school->id, 'fee_structure_id' => $structure->id, 'name' => 'Fee', 'amount' => 100000, 'type' => 'tuition', 'due_date' => today()->addDays(7)]);
        $invoice = app(InvoiceService::class)->generateForStudent($structure, $student);

        $service = app(CollectionService::class);
        $service->schedule(today());
        $service->schedule(today());
        $this->assertDatabaseHas('collection_reminders', ['invoice_id' => $invoice->id, 'type' => 'DUE']);
        $this->assertSame(1, \App\Models\CollectionReminder::query()->where('invoice_id', $invoice->id)->count());
        $this->assertSame(1, \App\Models\NotificationQueue::query()->where('event', 'payment_reminder')->count());
    }
}
