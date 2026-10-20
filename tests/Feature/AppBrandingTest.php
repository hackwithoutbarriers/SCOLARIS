<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_logo_is_used_on_public_pages_and_filament_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('images/scolaris.webp')
            ->assertSee('images/scolaris.png')
            ->assertSee('manifest.webmanifest');

        $this->get('/register')
            ->assertOk()
            ->assertSee('images/scolaris.webp')
            ->assertSee('images/scolaris.png');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('images/scolaris.png');

        $this->get('/invitations/demo-token')
            ->assertOk()
            ->assertSee('images/scolaris.webp')
            ->assertSee('images/scolaris.png');

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/password/change')
            ->assertOk()
            ->assertSee('images/scolaris.png');

        $this->actingAs($user)
            ->get('/teacher/attendance')
            ->assertOk()
            ->assertSee('images/scolaris.png')
            ->assertSee('manifest.webmanifest');

        $this->get('/invitations/demo-token')
            ->assertOk()
            ->assertSee('images/scolaris.webp')
            ->assertSee('images/scolaris.png');

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/password/change')
            ->assertOk()
            ->assertSee('images/scolaris.png');

        $this->actingAs($user)
            ->get('/teacher/attendance')
            ->assertOk()
            ->assertSee('images/scolaris.png')
            ->assertSee('manifest.webmanifest');
    }

    public function test_logo_variants_and_manifest_icons_are_present(): void
    {
        foreach ([
            'images/scolaris.png',
            'images/scolaris.webp',
            'images/scolaris-192.png',
            'images/scolaris-512.png',
            'favicon.ico',
        ] as $asset) {
            $this->assertFileExists(public_path($asset));
        }

        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(
            ['/images/scolaris-192.png', '/images/scolaris-512.png'],
            array_column($manifest['icons'], 'src'),
        );
    }
}
