<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function store(Request $request, PaymentService $service): JsonResponse
    {
        abort_unless($request->user()->isFinanceOperator(), 403);
        abort_unless($request->header('Idempotency-Key'), 400, 'Idempotency-Key requis.');
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['required', Rule::in(['CASH', 'BANK', 'TMONEY', 'FLOOZ', 'OTHER'])],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);
        $student = \App\Models\Student::query()->findOrFail($data['student_id']);
        abort_unless($student->school_id === $request->user()->school_id, 403);
        $data['school_id'] = $student->school_id;
        $data['idempotency_key'] = $request->header('Idempotency-Key');
        return response()->json($service->recordManual($data)->load('receipt'), 201);
    }

    public function receipt(Payment $payment)
    {
        abort_unless(request()->user()->isFinanceOperator() && $payment->school_id === request()->user()->school_id, 403);
        $payment->load('student.guardians', 'receiver', 'receipt', 'allocations.invoice');
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.receipt', compact('payment'))
            ->stream($payment->receipt->number.'.pdf');
    }

    public function webhook(Request $request, string $provider, PaymentService $service): JsonResponse
    {
        $secret = (string) config('payments.webhook_secret');
        abort_unless($secret !== '', 503, 'Webhook provider non configuré.');
        $signature = (string) $request->header('X-Scolaris-Signature');
        abort_unless(hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature), 401, 'Signature webhook invalide.');
        $data = $request->validate([
            'merchant_reference' => ['required', 'string'],
            'provider_transaction_id' => ['required', 'string'],
            'status' => ['required', Rule::in(['SUCCESS', 'CONFIRMED', 'FAILED', 'EXPIRED', 'PENDING'])],
        ]);
        $transaction = PaymentTransaction::withoutGlobalScopes()
            ->where('provider', $provider)
            ->where('merchant_reference', $data['merchant_reference'])
            ->firstOrFail();
        if ($transaction->provider_transaction_id && $transaction->provider_transaction_id !== $data['provider_transaction_id']) abort(422, 'Référence fournisseur incohérente.');
        $status = match ($data['status']) {
            'SUCCESS', 'CONFIRMED' => Payment::CONFIRMED,
            'FAILED' => Payment::FAILED,
            'EXPIRED' => Payment::CANCELLED,
            default => Payment::PENDING,
        };
        return response()->json($service->handleProviderConfirmation($transaction, [
            'status' => $status,
            'provider_transaction_id' => $data['provider_transaction_id'],
            'response_payload' => ['status' => $data['status']],
        ]));
    }

    public function report(Request $request)
    {
        abort_unless($request->user()->isFinanceOperator(), 403);
        $type = $request->string('type', 'payments')->toString();
        abort_unless(in_array($type, ['payments', 'debtors'], true), 422);
        $filename = 'scolaris-'.$type.'-'.now()->format('Ymd').'.csv';
        return response()->streamDownload(function () use ($type): void {
            $handle = fopen('php://output', 'wb');
            if ($type === 'payments') {
                fputcsv($handle, ['date', 'eleve', 'classe', 'montant', 'methode', 'reference', 'caissier']);
                Payment::query()->with(['student', 'receiver'])->where('status', Payment::CONFIRMED)->orderBy('paid_at')->chunk(200, function ($payments) use ($handle): void {
                    foreach ($payments as $payment) fputcsv($handle, [$payment->paid_at?->toDateTimeString(), $payment->student->full_name, '', $payment->amount, $payment->payment_method, $payment->reference, $payment->receiver?->name]);
                });
            } else {
                fputcsv($handle, ['eleve', 'classe', 'montant_du', 'montant_paye', 'solde', 'jours_retard']);
                Invoice::query()->with(['student', 'classRoom', 'allocations.payment.reversals'])->whereNotIn('status', [Invoice::CANCELLED, Invoice::PAID])->chunk(200, function ($invoices) use ($handle): void {
                    foreach ($invoices as $invoice) fputcsv($handle, [$invoice->student->full_name, $invoice->classRoom?->name, $invoice->total_amount, $invoice->paidAmount(), $invoice->balance(), max(0, $invoice->due_date->diffInDays(today(), false))]);
                });
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function debtors(Request $request): JsonResponse
    {
        abort_unless($request->user()->isFinanceOperator(), 403);
        $invoices = Invoice::query()->with(['student', 'classRoom', 'allocations.payment.reversals'])
            ->whereIn('status', [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE])
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->integer('student_id')))
            ->orderByDesc('due_date')->get();
        return response()->json($invoices->map(fn (Invoice $invoice) => [
            'student' => $invoice->student->full_name,
            'class' => $invoice->classRoom?->name,
            'due' => $invoice->total_amount,
            'paid' => $invoice->paidAmount(),
            'balance' => $invoice->balance(),
            'due_date' => $invoice->due_date->toDateString(),
            'days_late' => max(0, $invoice->due_date->diffInDays(today(), false)),
        ]));
    }
}
