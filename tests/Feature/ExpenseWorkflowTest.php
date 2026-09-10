<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\CashJournalService;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

class ExpenseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_records_expense_and_journal_recalculates_balance(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $this->actingAs($accountant);
        $expense = app(ExpenseService::class)->record([
            'school_id' => $school->id, 'category' => 'Fournitures', 'amount' => 15000,
            'description' => 'Papier', 'expense_date' => today()->toDateString(),
        ]);

        $this->assertSame('DEP-000001', $expense->voucher_number);
        $summary = app(CashJournalService::class)->summary($school->id, today());
        $this->assertSame(15000, $summary['expenses']);
        $this->assertSame(-15000, $summary['closing']);
    }

    public function test_only_director_can_reverse_expense_and_reason_is_required(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $this->actingAs($accountant);
        $expense = app(ExpenseService::class)->record([
            'school_id' => $school->id, 'category' => 'Entretien', 'amount' => 10000,
            'description' => 'Réparation', 'expense_date' => today()->toDateString(),
        ]);
        $this->expectException(AuthorizationException::class);
        app(ExpenseService::class)->reverse($expense, 10000, 'Correction suffisante');
    }

    public function test_director_can_reverse_with_a_long_reason(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $this->actingAs($director);
        $expense = app(ExpenseService::class)->record([
            'school_id' => $school->id, 'category' => 'Imprévus', 'amount' => 10000,
            'description' => 'Erreur de saisie', 'expense_date' => today()->toDateString(),
        ]);
        app(ExpenseService::class)->reverse($expense, 10000, 'Correction de saisie comptable');
        $this->assertSame(0, $expense->fresh()->netAmount());
    }
}
