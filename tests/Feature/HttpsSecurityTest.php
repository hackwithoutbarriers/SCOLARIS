<?php

namespace Tests\Feature;

use Tests\TestCase;

class HttpsSecurityTest extends TestCase
{
    public function test_session_cookie_defaults_match_local_and_production_requirements(): void
    {
        $this->assertFalse((bool) config('session.secure'));
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }

    public function test_hsts_is_only_added_in_production(): void
    {
        $this->get('/health')->assertHeaderMissing('Strict-Transport-Security');

        $this->app->detectEnvironment(fn (): string => 'production');
        $this->get('/health')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
