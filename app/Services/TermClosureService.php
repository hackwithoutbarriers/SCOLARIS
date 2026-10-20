<?php

namespace App\Services;

use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TermClosureService
{
    public function close(Term $term): Term
    {
        $user = auth()->user();
        abort_unless($user && $user->isDirector() && $term->school_id === $user->school_id, 403);

        if ($term->status === 'closed') {
            return $term;
        }

        $pending = $term->reportCards()
            ->whereIn('status', ['draft', 'review', 'conseil_de_classe', 'revision_requested'])
            ->count();

        if ($pending > 0) {
            throw ValidationException::withMessages([
                'term' => "La période contient encore {$pending} bulletin(s) à vérifier ou corriger.",
            ]);
        }

        return DB::transaction(function () use ($term, $user): Term {
            $term->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $user->id,
            ]);

            return $term->refresh();
        });
    }
}
