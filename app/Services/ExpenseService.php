<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseReversal;
use App\Models\ReceiptSequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public const DEFAULT_CATEGORIES = ['Salaires', 'Fournitures', 'Entretien', 'Transport', 'Énergie/eau', 'Communication', 'Imprévus'];

    public function record(array $data, ?UploadedFile $justification = null): Expense
    {
        Gate::authorize('create', Expense::class);

        return DB::transaction(function () use ($data, $justification): Expense {
            ReceiptSequence::query()->insertOrIgnore([
                'school_id' => $data['school_id'], 'sequence_type' => 'expense', 'next_number' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $sequence = ReceiptSequence::query()->where('school_id', $data['school_id'])
                ->where('sequence_type', 'expense')->lockForUpdate()->firstOrFail();
            $number = (int) $sequence->next_number;
            $sequence->increment('next_number');

            $path = $justification?->store('justifications', 'public') ?? ($data['justification'] ?? null);
            unset($data['justification']);
            return Expense::create([
                ...$data,
                'recorded_by' => $data['recorded_by'] ?? auth()->id(),
                'voucher_number' => 'DEP-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                'justification_path' => $path,
            ]);
        });
    }

    public function reverse(Expense $expense, int $amount, string $reason): ExpenseReversal
    {
        Gate::authorize('reverse', $expense);
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Le motif de correction doit comporter au moins 10 caractères.']);
        }

        return DB::transaction(function () use ($expense, $amount, $reason): ExpenseReversal {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            $available = (int) $expense->amount - (int) $expense->reversals()->sum('amount');
            if ($amount < 1 || $amount > $available) {
                throw ValidationException::withMessages(['amount' => 'Montant de reversal invalide pour cette dépense.']);
            }
            return ExpenseReversal::create([
                'school_id' => $expense->school_id, 'expense_id' => $expense->id,
                'amount' => $amount, 'reason' => $reason, 'created_by' => auth()->id(),
            ]);
        });
    }
}
