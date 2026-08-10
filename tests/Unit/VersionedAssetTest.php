<?php

namespace Tests\Unit;

use Tests\TestCase;

class VersionedAssetTest extends TestCase
{
    public function test_it_appends_the_configured_deployment_version(): void
    {
        config(['app.asset_version' => 'deploy 123']);

        $url = versioned_asset('epasien/assets/css/style.css');

        $this->assertStringEndsWith(
            '/epasien/assets/css/style.css?v=deploy%20123',
            $url
        );
    }

    public function test_it_uses_the_file_timestamp_outside_a_versioned_deployment(): void
    {
        config(['app.asset_version' => null]);

        $url = versioned_asset('epasien/assets/css/style.css');

        $this->assertMatchesRegularExpression(
            '~\/epasien/assets/css/style\.css\?v=\d+$~',
            $url
        );
    }
}
