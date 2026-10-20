<?php

namespace Tests\Feature;

use App\Filament\Pages\SchoolSupport;
use App\Filament\Pages\Dashboard;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSupportButtonStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_actions_include_their_filament_foreground_and_background_styles(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);
        Livewire::actingAs($admin)
            ->test(SchoolSupport::class)
            ->set('school', $school->id)
            ->call('openContext')
            ->assertRedirect(Dashboard::getUrl());

        $component = Livewire::actingAs($admin)
            ->test(SchoolSupport::class)
            ->set('school', $school->id)
            ->assertSee('Quitter le contexte')
            ->assertSee('Configurer')
            ->assertSee('Gérer');

        $html = $component->html();
        $this->assertMatchesRegularExpression(
            '/<button(?=[^>]*\bbg-custom-600\b)(?=[^>]*\btext-white\b)[^>]*>[\s\S]*?Configurer l\'école[\s\S]*?<\/button>/u',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<button(?=[^>]*\bbg-white\b)(?=[^>]*\btext-gray-950\b)[^>]*>[\s\S]*?Gérer les utilisateurs[\s\S]*?<\/button>/u',
            $html,
        );
    }
}
