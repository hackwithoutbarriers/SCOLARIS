<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceExportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_reports_and_receipts_are_limited_to_finance_roles_and_their_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $accountantA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'accountant']);
        $accountantB = User::factory()->create(['school_id' => $schoolB->id, 'role' => 'accountant']);
        $teacherA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'teacher']);

        $this->actingAs($accountantA);
        $paymentA = app(PaymentService::class)->recordManual([
            'school_id' => $schoolA->id,
            'student_id' => Student::factory()->create(['school_id' => $schoolA->id])->id,
            'amount' => 12000,
            'payment_method' => 'CASH',
            'idempotency_key' => 'school-a-payment',
        ]);

        $this->actingAs($accountantB);
        $paymentB = app(PaymentService::class)->recordManual([
            'school_id' => $schoolB->id,
            'student_id' => Student::factory()->create(['school_id' => $schoolB->id, 'last_name' => 'ÉcoleB'])->id,
            'amount' => 23000,
            'payment_method' => 'CASH',
            'idempotency_key' => 'school-b-payment',
        ]);

        $this->actingAs($teacherA)
            ->get('/api/payments/report')
            ->assertForbidden();
        $this->get(route('payments.receipt', $paymentA))->assertForbidden();

        $this->actingAs($accountantA)
            ->get(route('payments.receipt', $paymentA))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->get(route('payments.receipt', $paymentB))->assertNotFound();

        $report = $this->get('/api/payments/report')->assertOk()->streamedContent();
        $this->assertStringContainsString($paymentA->student->full_name, $report);
        $this->assertStringNotContainsString('ÉcoleB', $report);
    }
}
