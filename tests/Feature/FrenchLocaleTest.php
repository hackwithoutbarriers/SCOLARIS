<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrenchLocaleTest extends TestCase
{
    public function test_french_is_the_default_locale_and_language_switcher_keeps_french_available(): void
    {
        $this->assertSame('fr', config('app.locale'));
        $this->assertSame('Français', config('locales.available.fr'));

        $this->get('/language/fr')
            ->assertRedirect('/')
            ->assertSessionHas('locale', 'fr');
    }

    public function test_unsupported_language_cannot_be_selected(): void
    {
        $this->get('/language/en')->assertNotFound();
    }
}
