<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_offers_login_and_registration(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Se connecter')
            ->assertSee('Demander un accès')
            ->assertSee('Démarrage guidé');
    }
}
