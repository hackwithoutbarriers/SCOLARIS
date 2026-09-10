<?php

namespace App\Services;

use App\Models\CashOpeningBalance;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CashJournalService
{
    public function summary(int $schoolId, CarbonInterface $date): array
    {
        $opening = CashOpeningBalance::query()->where('school_id', $schoolId)->whereDate('balance_date', $date)->value('amount');
        if ($opening === null) {
            $opening = $this->closing($schoolId, $date->copy()->subDay());
        }
        $receipts = (int) Payment::query()->where('school_id', $schoolId)->where('payment_method', 'CASH')
            ->where('status', Payment::CONFIRMED)->whereDate('paid_at', $date)->sum('amount');
        $expenses = (int) Expense::query()->with('reversals')->where('school_id', $schoolId)
            ->whereDate('expense_date', $date)->get()->sum(fn (Expense $expense): int => $expense->netAmount());

        return ['date' => $date, 'opening' => (int) $opening, 'receipts' => $receipts, 'expenses' => $expenses, 'closing' => (int) $opening + $receipts - $expenses];
    }

    public function closing(int $schoolId, CarbonInterface $date): int
    {
        if ($date->isBefore($date->copy()->startOfYear()->subYears(10))) {
            return 0;
        }
        $summary = $this->summaryWithoutRecursion($schoolId, $date);
        return $summary['closing'];
    }

    private function summaryWithoutRecursion(int $schoolId, CarbonInterface $date): array
    {
        $opening = CashOpeningBalance::query()->where('school_id', $schoolId)->whereDate('balance_date', $date)->value('amount');
        if ($opening === null) {
            $previous = CashOpeningBalance::query()->where('school_id', $schoolId)->whereDate('balance_date', '<', $date)->latest('balance_date')->first();
            $opening = $previous ? (int) $previous->amount : 0;
            if ($previous) {
                $opening += (int) Payment::query()->where('school_id', $schoolId)->where('payment_method', 'CASH')->where('status', Payment::CONFIRMED)
                    ->whereBetween('paid_at', [$previous->balance_date->startOfDay(), $date->copy()->subDay()->endOfDay()])->sum('amount');
                $opening -= (int) Expense::query()->with('reversals')->where('school_id', $schoolId)
                    ->whereBetween('expense_date', [$previous->balance_date, $date->copy()->subDay()])->get()->sum(fn (Expense $expense): int => $expense->netAmount());
            }
        }
        $receipts = (int) Payment::query()->where('school_id', $schoolId)->where('payment_method', 'CASH')->where('status', Payment::CONFIRMED)->whereDate('paid_at', $date)->sum('amount');
        $expenses = (int) Expense::query()->with('reversals')->where('school_id', $schoolId)->whereDate('expense_date', $date)->get()->sum(fn (Expense $expense): int => $expense->netAmount());
        return ['closing' => (int) $opening + $receipts - $expenses];
    }
}
