<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_record_or_export_finances(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $response = $this->actingAs($teacher)->postJson('/api/payments', [
            'student_id' => $student->id, 'amount' => 1000, 'payment_method' => 'CASH',
        ], ['Idempotency-Key' => 'test-1']);
        $response->assertForbidden();
        $this->actingAs($teacher)->getJson('/api/payments/debtors')->assertForbidden();
        $this->actingAs($teacher)->get('/api/payments/report')->assertForbidden();
    }

    public function test_manual_payment_idempotency_returns_one_payment(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $payload = ['student_id' => $student->id, 'amount' => 1000, 'payment_method' => 'CASH'];
        $headers = ['Idempotency-Key' => 'same-request'];
        $first = $this->actingAs($accountant)->postJson('/api/payments', $payload, $headers)->assertCreated();
        $second = $this->actingAs($accountant)->postJson('/api/payments', $payload, $headers)->assertSuccessful();
        $this->assertSame($first->json('id'), $second->json('id'));
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_webhook_requires_signature_and_does_not_accept_client_amount_or_student(): void
    {
        $school = School::factory()->create();
        $transaction = PaymentTransaction::create([
            'school_id' => $school->id, 'provider' => 'fake', 'merchant_reference' => 'merchant-1',
        ]);
        config(['payments.webhook_secret' => 'secret']);
        $payload = ['merchant_reference' => 'merchant-1', 'provider_transaction_id' => 'provider-1', 'status' => 'SUCCESS', 'amount' => 999999, 'student_id' => 999999];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $this->postJson('/api/payments/webhooks/fake', $payload)->assertUnauthorized();
        $this->withHeader('X-Scolaris-Signature', hash_hmac('sha256', $body, 'secret'))
            ->postJson('/api/payments/webhooks/fake', $payload)
            ->assertOk();
        $this->assertSame('CONFIRMED', $transaction->fresh()->status);
    }

    public function test_cross_tenant_receipt_is_not_accessible(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $userA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'director']);
        $payment = Payment::create(['school_id' => $schoolB->id, 'student_id' => Student::factory()->create(['school_id' => $schoolB->id])->id, 'amount' => 1000, 'payment_method' => 'CASH', 'status' => Payment::PENDING]);
        $this->actingAs($userA)->get('/payments/'.$payment->id.'/receipt')->assertNotFound();
    }
}
