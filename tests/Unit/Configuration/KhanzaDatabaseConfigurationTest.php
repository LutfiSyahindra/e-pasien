<?php

namespace Tests\Unit\Configuration;

use Tests\TestCase;

class KhanzaDatabaseConfigurationTest extends TestCase
{
    public function test_khanza_connection_remains_strict_but_accepts_legacy_zero_dates(): void
    {
        $modes = config('database.connections.mysql_khanza.modes');

        $this->assertContains('STRICT_TRANS_TABLES', $modes);
        $this->assertNotContains('NO_ZERO_DATE', $modes);
        $this->assertNotContains('NO_ZERO_IN_DATE', $modes);
    }
}
