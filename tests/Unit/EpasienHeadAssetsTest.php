<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;

class EpasienHeadAssetsTest extends TestCase
{
    public function test_regular_menu_avoids_noncritical_global_styles(): void
    {
        $head = $this->renderHeadFor('/e-pasien/menu/fasilitas-tarif/kamar');

        $this->assertStringNotContainsString('fonts.googleapis.com', $head);
        $this->assertStringNotContainsString('dark-theme.css', $head);
        $this->assertStringNotContainsString('access-control.css', $head);
        $this->assertStringNotContainsString('sweetalert-premium.css', $head);
        $this->assertStringContainsString('sidebar-premium.css', $head);
    }

    public function test_sweetalert_menu_loads_only_compact_sweetalert_styles(): void
    {
        $head = $this->renderHeadFor('/e-pasien/menu/daftar-online');

        $this->assertStringNotContainsString('access-control.css', $head);
        $this->assertStringContainsString('sweetalert-premium.css', $head);
        $this->assertStringContainsString('select2.min.css', $head);
    }

    public function test_access_management_keeps_its_required_plugins_and_styles(): void
    {
        $head = $this->renderHeadFor('/e-pasien/settings/auth/users');

        $this->assertStringContainsString('access-control.css', $head);
        $this->assertStringNotContainsString('sweetalert-premium.css', $head);
        $this->assertStringContainsString('dataTables.bootstrap5.min.css', $head);
        $this->assertStringContainsString('select2.min.css', $head);
    }

    private function renderHeadFor(string $uri): string
    {
        $request = Request::create($uri, 'GET');
        $route = $this->app['router']->getRoutes()->match($request);

        $request->setRouteResolver(static fn () => $route);
        $this->app->instance('request', $request);

        return view('template.epasien.head')->render();
    }
}
