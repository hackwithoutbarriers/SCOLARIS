<?php

namespace App\Services;

use App\Models\CashClosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashClosureService
{
    public function submit(int $schoolId, string $date, int $physicalBalance, ?string $notes = null): CashClosure
    {
        $summary = app(CashJournalService::class)->summary($schoolId, \Carbon\Carbon::parse($date));
        $existing = CashClosure::query()->where('school_id', $schoolId)->whereDate('closure_date', $date)->first();
        if ($existing?->status === 'closed') {
            throw ValidationException::withMessages(['closure' => 'Une clôture validée est immuable. Utilisez une correction tracée.']);
        }
        if ($physicalBalance !== $summary['closing'] && mb_strlen(trim((string) $notes)) < 10) {
            throw ValidationException::withMessages(['notes' => 'Une explication d’au moins 10 caractères est obligatoire en cas d’écart.']);
        }

        return DB::transaction(fn (): CashClosure => CashClosure::updateOrCreate(
            ['school_id' => $schoolId, 'closure_date' => $date],
            [
                'theoretical_balance' => $summary['closing'],
                'physical_balance' => $physicalBalance,
                'variance' => $physicalBalance - $summary['closing'],
                'status' => 'pending',
                'counted_by' => auth()->id(),
                'approved_by' => null,
                'approved_at' => null,
                'notes' => $notes,
            ],
        ));
    }

    public function approve(CashClosure $closure): CashClosure
    {
        if (! auth()->user()?->isDirector() || $closure->school_id !== auth()->user()->school_id) {
            abort(403);
        }
        if ($closure->status !== 'pending') {
            throw ValidationException::withMessages(['closure' => 'Cette clôture est déjà validée.']);
        }

        $closure->update(['status' => 'closed', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return $closure;
    }
}
