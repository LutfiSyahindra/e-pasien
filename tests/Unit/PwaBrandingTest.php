<?php

namespace Tests\Unit;

use Tests\TestCase;

class PwaBrandingTest extends TestCase
{
    public function test_manifest_uses_rs_arsy_icons_with_valid_dimensions(): void
    {
        $manifest = json_decode(
            file_get_contents(public_path('manifest.webmanifest')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $expectedIcons = [
            '/epasien/assets/images/pwa-icon-192.png' => '192x192',
            '/epasien/assets/images/pwa-icon-512.png' => '512x512',
            '/epasien/assets/images/pwa-icon-maskable-512.png' => '512x512',
        ];

        $this->assertSame(array_keys($expectedIcons), array_column($manifest['icons'], 'src'));

        foreach ($manifest['icons'] as $icon) {
            $path = public_path(ltrim($icon['src'], '/'));
            $size = getimagesize($path);

            $this->assertNotFalse($size);
            $this->assertSame($expectedIcons[$icon['src']], $size[0].'x'.$size[1]);
            $this->assertSame($expectedIcons[$icon['src']], $icon['sizes']);
            $this->assertSame('image/png', $size['mime']);
        }
    }

    public function test_installed_app_splash_uses_the_epasien_logo(): void
    {
        $splash = file_get_contents(resource_path('views/template/epasien/pwa-splash.blade.php'));
        $appLayout = file_get_contents(resource_path('views/template/epasien/appPasien.blade.php'));
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertStringContainsString('imagesArsy/epasien.png', $splash);
        $this->assertStringContainsString('@include("template.epasien.pwa-splash")', $appLayout);
        $this->assertStringContainsString('@include("template.epasien.pwa-splash")', $login);
    }
}
