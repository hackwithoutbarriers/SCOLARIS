<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Receipt;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReceiptDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_download_reports_a_confirmed_payment_without_a_receipt_without_throwing_a_500(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $payment = Payment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'amount' => 1000,
            'payment_method' => 'CASH',
            'status' => Payment::CONFIRMED,
            'paid_at' => now(),
        ]);

        $this->actingAs($accountant)
            ->get('/payments/'.$payment->id.'/receipt')
            ->assertStatus(409)
            ->assertSeeText('Le reçu de ce paiement n’est pas disponible.');
    }

    public function test_accountant_can_stream_an_existing_receipt_as_a_pdf(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $payment = Payment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'amount' => 1000,
            'payment_method' => 'CASH',
            'status' => Payment::CONFIRMED,
            'paid_at' => now(),
        ]);
        Receipt::create([
            'school_id' => $school->id,
            'payment_id' => $payment->id,
            'number' => 'REC-000001',
            'balance_after' => 500,
        ]);

        $response = $this->actingAs($accountant)
            ->get('/payments/'.$payment->id.'/receipt')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
