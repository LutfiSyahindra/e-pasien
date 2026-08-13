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

        $this->assertSame('/dashboard', $manifest['id']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('id-ID', $manifest['lang']);
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

    public function test_install_experience_uses_a_user_gesture_without_requesting_notification_permission(): void
    {
        $installer = file_get_contents(public_path('epasien/assets/js/pwa-install.js'));
        $installerStyles = file_get_contents(public_path('epasien/assets/css/pwa-install.css'));
        $landingHead = file_get_contents(resource_path('views/template/landing/head.blade.php'));

        $this->assertStringContainsString("window.addEventListener('beforeinstallprompt'", $installer);
        $this->assertStringContainsString("window.navigator.serviceWorker.register('/sw.js'", $installer);
        $this->assertStringContainsString("event.target.closest('[data-pwa-install]')", $installer);
        $this->assertStringContainsString("document.body.classList.add('pwa-toast-visible')", $installer);
        $this->assertStringContainsString('min-height: 44px', $installerStyles);
        $this->assertStringNotContainsString('Notification.requestPermission', $installer);
        $this->assertStringContainsString('epasien/assets/js/pwa-install.js', $landingHead);
        $this->assertStringContainsString('manifest.webmanifest', $landingHead);
    }

    public function test_notification_activation_is_enforced_only_after_login(): void
    {
        $app = file_get_contents(resource_path('js/app.js'));
        $notificationCenter = file_get_contents(resource_path('js/notification-center.js'));
        $authenticatedHead = file_get_contents(resource_path('views/template/epasien/head.blade.php'));
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertStringContainsString('@auth', $authenticatedHead);
        $this->assertStringContainsString('meta name="epasien-user-id"', $authenticatedHead);
        $this->assertStringContainsString(
            "if (document.querySelector('meta[name=\"epasien-user-id\"]'))",
            $app,
        );
        $this->assertStringContainsString(
            "window.addEventListener('epasien:patient-service-read', syncPatientServiceRead);\n    enforceRequiredNotifications();\n    subscribeToRealtime();",
            $notificationCenter,
        );
        $this->assertStringNotContainsString('epasien-user-id', $login);
    }
}
