<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveAcademicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_login_is_available_for_mobile_and_desktop_layouts(): void
    {
        foreach ([320, 375, 390, 768, 1280] as $viewport) {
            $this->withHeader('Viewport-Width', (string) $viewport)
                ->get('/admin/login')
                ->assertOk()
                ->assertSee('Adresse e-mail')
                ->assertSee('Mot de passe');
        }
    }

    /**
     * Server-side smoke coverage for the mobile-first pages. Visual viewport checks
     * are documented in docs/responsive-validation.md because this project has no browser runner.
     */
    public function test_grade_entry_and_import_pages_are_available_at_all_supported_viewports(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        foreach ([320, 375, 390, 768, 1280] as $viewport) {
            $this->withHeader('Viewport-Width', (string) $viewport)->actingAs($user)
                ->get('/admin/grade-entry')->assertOk()->assertSee('Saisie rapide des notes');
            $this->withHeader('Viewport-Width', (string) $viewport)->actingAs($user)
                ->get('/admin/grade-import')->assertOk()->assertSee('Importer des notes CSV');
        }
    }

    public function test_finance_pages_are_available_at_all_supported_viewports(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        foreach ([320, 375, 390, 768, 1280] as $viewport) {
            $headers = ['Viewport-Width' => (string) $viewport];
            $this->withHeaders($headers)->actingAs($user)->get('/admin/payments')
                ->assertOk()->assertSee('Paiements');
            $this->withHeaders($headers)->actingAs($user)->get('/admin/payments/create')
                ->assertOk()->assertSee('Montant');
            $this->withHeaders($headers)->actingAs($user)->get('/admin/fee-structures')
                ->assertOk()->assertSee('Grilles tarifaires');
        }
    }
}
