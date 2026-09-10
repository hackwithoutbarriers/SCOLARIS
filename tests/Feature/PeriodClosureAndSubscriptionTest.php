<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Term;
use App\Models\User;
use App\Services\TermClosureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodClosureAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_close_a_period_and_closed_dates_are_locked(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
        $term = Term::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 1',
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-20',
            'sort_order' => 1,
        ]);

        $this->actingAs($director);
        app(TermClosureService::class)->close($term);

        $this->assertSame('closed', $term->refresh()->status);
        $this->assertTrue(Term::isClosedForDate($school->id, $year->id, '2026-10-01'));
    }

    public function test_subscription_management_is_reserved_for_the_saas_owner(): void
    {
        $school = School::factory()->create();
        $plan = SubscriptionPlan::create(['name' => 'Essentiel', 'code' => 'essential', 'monthly_amount' => 25000]);
        SchoolSubscription::create([
            'school_id' => $school->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => today(),
        ]);
        $owner = User::factory()->create(['role' => 'super_admin', 'school_id' => null]);
        $director = User::factory()->create(['role' => 'director', 'school_id' => $school->id]);

        $this->actingAs($owner)->get('/admin/school-subscriptions')->assertOk();
        $this->actingAs($director)->get('/admin/school-subscriptions')->assertForbidden();
    }
}
