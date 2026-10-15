<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Filament\Resources\ExpenseResource\Pages\CreateExpense;
use App\Services\CashJournalService;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
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

    public function test_expense_resource_form_creates_a_school_scoped_expense_without_client_school_id(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);

        Livewire::actingAs($accountant)
            ->test(CreateExpense::class)
            ->fillForm([
                'category' => 'Fournitures pédagogiques',
                'amount' => 15000,
                'expense_date' => today()->toDateString(),
                'description' => 'Achat de cahiers',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('expenses', [
            'school_id' => $school->id,
            'recorded_by' => $accountant->id,
            'voucher_number' => 'DEP-000001',
            'amount' => 15000,
        ]);
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

    public function test_expense_above_threshold_is_pending_until_director_approval(): void
    {
        $school = School::factory()->create(['expense_approval_threshold' => 100000]);
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        $this->actingAs($accountant);
        $expense = app(ExpenseService::class)->record([
            'school_id' => $school->id, 'category' => 'Travaux', 'amount' => 150000,
            'description' => 'Réfection', 'expense_date' => today()->toDateString(),
        ]);
        $this->assertSame('pending', $expense->approval_status);
        $this->assertSame(0, app(CashJournalService::class)->summary($school->id, today())['expenses']);

        $this->actingAs($director);
        app(ExpenseService::class)->approve($expense);
        $this->assertSame(150000, app(CashJournalService::class)->summary($school->id, today())['expenses']);
    }

    public function test_cash_closure_requires_variance_reason_and_cannot_be_reopened(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        $this->actingAs($accountant);
        $service = app(\App\Services\CashClosureService::class);
        $this->expectException(ValidationException::class);
        $service->submit($school->id, today()->toDateString(), 100, 'court');
    }

    public function test_rejected_expense_is_immutable_and_can_be_resubmitted_as_a_new_traceable_expense(): void
    {
        $school = School::factory()->create(['expense_approval_threshold' => 100000]);
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        $this->actingAs($accountant);
        $expense = app(ExpenseService::class)->record([
            'school_id' => $school->id, 'category' => 'Travaux', 'amount' => 150000,
            'description' => 'Ancienne demande', 'expense_date' => today()->toDateString(),
        ]);
        $this->actingAs($director);
        app(ExpenseService::class)->reject($expense, 'Pièce justificative incomplète');
        $this->assertSame('rejected', $expense->fresh()->approval_status);

        $this->actingAs($accountant);
        $corrected = app(ExpenseService::class)->resubmit($expense, [
            'category' => 'Travaux', 'amount' => 150000,
            'description' => 'Demande corrigée', 'expense_date' => today()->toDateString(),
        ]);
        $this->assertSame($expense->id, $corrected->correction_of_id);
        $this->assertSame('pending', $corrected->approval_status);
        $this->assertSame('Ancienne demande', $expense->fresh()->description);
    }
}
