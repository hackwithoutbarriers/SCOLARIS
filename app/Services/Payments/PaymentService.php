<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentReversal;
use App\Models\PaymentTransaction;
use App\Models\Receipt;
use App\Models\ReceiptSequence;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function handlerFor(string $method): PaymentMethodHandler
    {
        return match ($method) {
            'CASH' => app(CashPaymentHandler::class),
            'BANK', 'TMONEY', 'FLOOZ', 'OTHER' => app(LegacyPaymentMethodHandler::class),
            default => throw ValidationException::withMessages(['payment_method' => 'Mode de paiement invalide.']),
        };
    }

    public function recordManual(array $data): Payment
    {
        try {
            return DB::transaction(function () use ($data): Payment {
                if (! empty($data['idempotency_key'])) {
                    $existing = Payment::query()
                        ->where('school_id', $data['school_id'])
                        ->where('idempotency_key', $data['idempotency_key'])
                        ->first();
                    if ($existing) {
                        return $existing->load('allocations.invoice', 'receipt');
                    }
                }
                $payment = Payment::create([
                    ...$data,
                    'status' => Payment::CONFIRMED,
                    'paid_at' => $data['paid_at'] ?? now(),
                    'received_by' => $data['received_by'] ?? auth()->id(),
                ]);

                return $this->confirm($payment);
            });
        } catch (QueryException $exception) {
            if (! empty($data['idempotency_key']) && str_contains(strtolower($exception->getMessage()), 'unique')) {
                return Payment::query()
                    ->where('school_id', $data['school_id'])
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->firstOrFail();
            }
            throw $exception;
        }
    }

    public function confirm(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === Payment::CONFIRMED && $payment->receipt()->exists()) {
                return $payment->load('allocations.invoice', 'receipt');
            }
            if (in_array($payment->status, [Payment::FAILED, Payment::CANCELLED, Payment::REFUNDED], true)) {
                throw ValidationException::withMessages(['status' => 'Ce paiement ne peut pas être confirmé.']);
            }

            $payment = $this->handlerFor($payment->payment_method)->confirm($payment);
            $payment->update(['status' => Payment::CONFIRMED, 'paid_at' => $payment->paid_at ?? now()]);
            $remaining = (int) $payment->amount;
            $invoices = Invoice::query()
                ->where('student_id', $payment->student_id)
                ->whereIn('status', [Invoice::ISSUED, Invoice::PARTIALLY_PAID, Invoice::OVERDUE])
                ->orderBy('due_date')->orderBy('id')
                ->lockForUpdate()->get();

            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }
                $allocation = min($remaining, $invoice->balance());
                if ($allocation <= 0) {
                    continue;
                }
                PaymentAllocation::firstOrCreate(
                    ['payment_id' => $payment->id, 'invoice_id' => $invoice->id],
                    ['school_id' => $payment->school_id, 'amount' => $allocation]
                );
                $remaining -= $allocation;
                $invoice->refreshStatus();
            }

            $payment->update(['unallocated_amount' => $remaining]);
            $balance = (int) Invoice::query()->where('student_id', $payment->student_id)
                ->whereNotIn('status', [Invoice::CANCELLED])->get()
                ->sum(fn (Invoice $invoice): int => $invoice->balance());
            ReceiptSequence::query()->insertOrIgnore([
                'school_id' => $payment->school_id,
                'sequence_type' => 'receipt',
                'next_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sequence = ReceiptSequence::query()
                ->where('school_id', $payment->school_id)
                ->where('sequence_type', 'receipt')
                ->lockForUpdate()
                ->firstOrFail();
            $number = (int) $sequence->next_number;
            $sequence->increment('next_number');
            $receipt = Receipt::firstOrCreate(
                ['payment_id' => $payment->id],
                ['school_id' => $payment->school_id, 'number' => 'REC-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT), 'balance_after' => $balance]
            );
            if ($receipt->balance_after !== $balance) {
                $receipt->update(['balance_after' => $balance]);
            }
            $payment->load('student', 'allocations.invoice');
            foreach ($payment->allocations as $allocation) {
                app(NotificationService::class)->queueFinancial($payment->student, $allocation->invoice, 'payment_received', null, [
                    'amount' => $allocation->amount,
                    'balance' => $allocation->invoice->balance(),
                    'reference' => $payment->reference,
                    'payment_id' => $payment->id,
                ]);
            }

            return $payment->load('allocations.invoice', 'receipt');
        });
    }

    public function createProviderTransaction(array $data): PaymentTransaction
    {
        return PaymentTransaction::create([
            'school_id' => $data['school_id'],
            'payment_id' => $data['payment_id'] ?? null,
            'provider' => $data['provider'] ?? config('payments.gateway', 'manual'),
            'merchant_reference' => $data['merchant_reference'],
            'status' => Payment::PENDING,
            'request_payload' => $data['request_payload'] ?? null,
        ]);
    }

    public function reverse(Payment $payment, int $amount, string $reason): PaymentReversal
    {
        Gate::authorize('reverse', $payment);
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Le motif de correction doit comporter au moins 10 caractères.']);
        }

        return DB::transaction(function () use ($payment, $amount, $reason): PaymentReversal {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $reversed = (int) $payment->reversals()->sum('amount');
            if ($payment->status !== Payment::CONFIRMED || $amount < 1 || $amount > $payment->amount - $reversed) {
                throw ValidationException::withMessages(['amount' => 'Montant de reversal invalide pour ce paiement.']);
            }
            $remaining = $amount;
            $reversal = null;
            foreach ($payment->allocations as $allocation) {
                if ($remaining <= 0) {
                    break;
                }
                $alreadyReversed = (int) PaymentReversal::query()->where('payment_id', $payment->id)->where('invoice_id', $allocation->invoice_id)->sum('amount');
                $available = max(0, $allocation->amount - $alreadyReversed);
                $portion = min($remaining, $available);
                if ($portion === 0) {
                    continue;
                }
                $reversal = PaymentReversal::create([
                    'school_id' => $payment->school_id, 'payment_id' => $payment->id, 'invoice_id' => $allocation->invoice_id,
                    'amount' => $portion, 'reason' => $reason, 'created_by' => auth()->id(),
                ]);
                $remaining -= $portion;
                $allocation->invoice->refreshStatus();
            }
            if ($remaining > 0) {
                $reversal = PaymentReversal::create([
                    'school_id' => $payment->school_id, 'payment_id' => $payment->id,
                    'amount' => $remaining, 'reason' => $reason, 'created_by' => auth()->id(),
                ]);
            }

            return $reversal;
        });
    }

    public function handleProviderConfirmation(PaymentTransaction $transaction, array $payload): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $payload): PaymentTransaction {
            $transaction = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->status === Payment::CONFIRMED) {
                return $transaction;
            }
            if ($transaction->payment?->status === Payment::CONFIRMED) {
                return $transaction;
            }
            $transaction->update([
                'provider_transaction_id' => $payload['provider_transaction_id'] ?? $transaction->provider_transaction_id,
                'status' => $payload['status'],
                'response_payload' => $payload['response_payload'] ?? null,
                'attempts' => $transaction->attempts + 1,
            ]);
            if ($payload['status'] === Payment::CONFIRMED && $transaction->payment) {
                $this->confirm($transaction->payment);
            } elseif (in_array($payload['status'], [Payment::FAILED, Payment::CANCELLED], true) && $transaction->payment) {
                $transaction->payment->update(['status' => $payload['status']]);
            }

            return $transaction->fresh('payment');
        });
    }
}
