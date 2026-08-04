<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\PermissionMiddleware;

trait AuthorizesEpasienMenuRoutes
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // These classes exercise page behavior. Permission enforcement has its
        // own database-backed coverage in EpasienMenuAuthorizationTest.
        $this->withoutMiddleware(PermissionMiddleware::class);
    }
}
