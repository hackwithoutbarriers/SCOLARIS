<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\School;
use App\Models\ExpenseReversal;
use App\Models\ReceiptSequence;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public const DEFAULT_CATEGORIES = [
        'Salaires et prestations',
        'Fournitures pédagogiques',
        'Entretien et réparations',
        'Eau, électricité et télécommunications',
        'Transport',
        'Cantine',
        'Administration',
        'Autres',
    ];

    public function record(array $data, ?UploadedFile $justification = null): Expense
    {
        Gate::authorize('create', Expense::class);
        $schoolId = (int) auth()->user()->school_id;
        if ($schoolId < 1) {
            throw new AuthorizationException('Une école active est requise pour enregistrer une dépense.');
        }

        return DB::transaction(function () use ($data, $justification, $schoolId): Expense {
            ReceiptSequence::query()->insertOrIgnore([
                'school_id' => $schoolId, 'sequence_type' => 'expense', 'next_number' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $sequence = ReceiptSequence::query()->where('school_id', $schoolId)
                ->where('sequence_type', 'expense')->lockForUpdate()->firstOrFail();
            $number = (int) $sequence->next_number;
            $sequence->increment('next_number');

            $path = $justification?->store('justifications', 'public') ?? ($data['justification'] ?? null);
            unset($data['justification']);
            $school = School::query()->findOrFail($schoolId);
            return Expense::create([
                ...$data,
                'school_id' => $schoolId,
                'recorded_by' => auth()->id(),
                'voucher_number' => 'DEP-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                'justification_path' => $path,
                'approval_status' => (int) $data['amount'] > (int) $school->expense_approval_threshold ? 'pending' : 'approved',
            ]);
        });
    }

    public function approve(Expense $expense, string $reason = ''): Expense
    {
        Gate::authorize('approve', $expense);
        return tap($expense)->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_reason' => $reason ?: null,
        ]);
    }

    public function reject(Expense $expense, string $reason): Expense
    {
        Gate::authorize('reject', $expense);
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Le motif du rejet doit comporter au moins 10 caractères.']);
        }

        return tap($expense)->update([
            'approval_status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'approval_reason' => $reason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);
    }

    public function resubmit(Expense $expense, array $data): Expense
    {
        Gate::authorize('resubmit', $expense);

        return $this->record([
            ...$data,
            'school_id' => $expense->school_id,
            'correction_of_id' => $expense->id,
        ]);
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
