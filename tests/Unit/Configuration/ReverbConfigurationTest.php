<?php

namespace Tests\Unit\Configuration;

use Tests\TestCase;

class ReverbConfigurationTest extends TestCase
{
    public function test_allowed_origins_are_normalized_to_hostnames(): void
    {
        $allowedOrigins = config('reverb.apps.apps.0.allowed_origins');

        $this->assertContains('localhost', $allowedOrigins);
        $this->assertNotContains('http://localhost', $allowedOrigins);
    }
}
