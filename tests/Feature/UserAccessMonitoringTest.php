<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserAccessMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private const DEVICE_UUID = '8749e7d4-d52e-4d70-b13e-253f789ba116';

    public function test_guest_cannot_record_or_view_user_access(): void
    {
        $this->postJson(route('userAccess.store'), [
            'device_uuid' => self::DEVICE_UUID,
            'mode' => 'web',
            'installed' => false,
        ])->assertUnauthorized();

        $this->get(route('userAccessMonitoring.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_access_is_recorded_per_device_and_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) AppleWebKit Chrome/125.0 Mobile')
            ->postJson(route('userAccess.store'), [
                'device_uuid' => self::DEVICE_UUID,
                'mode' => 'web',
                'installed' => false,
            ])
            ->assertOk()
            ->assertJson(['recorded' => true]);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) AppleWebKit Chrome/125.0 Mobile')
            ->postJson(route('userAccess.store'), [
                'device_uuid' => self::DEVICE_UUID,
                'mode' => 'pwa',
                'installed' => true,
            ])
            ->assertOk();

        $this->assertDatabaseCount('user_access_devices', 1);
        $this->assertDatabaseHas('user_access_devices', [
            'user_id' => $user->id,
            'device_uuid' => self::DEVICE_UUID,
            'platform' => 'Android',
            'browser' => 'Google Chrome',
            'last_mode' => 'pwa',
            'is_pwa_installed' => true,
        ]);
        $this->assertDatabaseHas('user_access_daily', [
            'user_id' => $user->id,
            'access_date' => now()->toDateString(),
            'mode' => 'web',
            'visit_count' => 1,
        ]);
        $this->assertDatabaseHas('user_access_daily', [
            'user_id' => $user->id,
            'access_date' => now()->toDateString(),
            'mode' => 'pwa',
            'visit_count' => 1,
        ]);
    }

    public function test_repeated_access_updates_the_existing_daily_record(): void
    {
        $user = User::factory()->create();
        $payload = [
            'device_uuid' => self::DEVICE_UUID,
            'mode' => 'web',
            'installed' => false,
        ];

        $this->actingAs($user)->postJson(route('userAccess.store'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('userAccess.store'), $payload)->assertOk();

        $this->assertDatabaseCount('user_access_devices', 1);
        $this->assertDatabaseCount('user_access_daily', 1);
        $this->assertDatabaseHas('user_access_daily', [
            'user_id' => $user->id,
            'mode' => 'web',
            'visit_count' => 2,
        ]);
    }

    public function test_tracking_payload_is_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('userAccess.store'), [
                'device_uuid' => 'not-a-uuid',
                'mode' => 'desktop',
                'installed' => 'maybe',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_uuid', 'mode', 'installed']);
    }

    public function test_authorized_admin_can_view_monitoring_summary_and_filters(): void
    {
        $patient = User::factory()->create(['name' => 'Pasien PWA']);
        $admin = $this->monitoringAdmin();

        $this->actingAs($patient)->postJson(route('userAccess.store'), [
            'device_uuid' => self::DEVICE_UUID,
            'mode' => 'pwa',
            'installed' => true,
        ])->assertOk();

        $this->actingAs($admin)
            ->get(route('userAccessMonitoring.index', [
                'period' => 7,
                'channel' => 'installed',
                'q' => 'Pasien PWA',
            ]))
            ->assertOk()
            ->assertViewIs('e-pasien.settings.userAccessMonitoring.index')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['pwa_users'] === 1
                && $summary['installed_users'] === 1
                && $summary['installed_devices'] === 1)
            ->assertSeeText('Pemantauan Penggunaan Web & PWA')
            ->assertSeeText('Pasien PWA')
            ->assertSee('user-access-monitoring.css')
            ->assertSee('epasien-access-tracking-url');
    }

    public function test_user_without_monitoring_permission_cannot_view_report(): void
    {
        $permission = Permission::findOrCreate('EPASIEN.SETTINGS', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $this->actingAs($user)
            ->get(route('userAccessMonitoring.index'))
            ->assertForbidden();
    }

    private function monitoringAdmin(): User
    {
        $permissions = collect([
            'EPASIEN.SETTINGS',
            'EPASIEN.SETTINGS.USAGE_MONITORING',
        ])->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
        $admin = User::factory()->create(['name' => 'Admin E-Pasien']);
        $admin->givePermissionTo($permissions->all());

        return $admin;
    }
}
