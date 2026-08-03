<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\User;
use App\Notifications\PromotionPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PromotionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['EPASIEN.MENU', 'EPASIEN.MENU.PROMOSI', 'EPASIEN.MENU.PROMOSI.KELOLA'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_patient_only_sees_promotions_that_are_currently_active(): void
    {
        $patient = $this->patient();
        $this->promotion(['title' => 'Promo Aktif']);
        $this->promotion(['title' => 'Promo Draf', 'status' => Promotion::STATUS_DRAFT]);
        $this->promotion(['title' => 'Promo Berakhir', 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDay()]);

        $this->actingAs($patient)
            ->get(route('promotions.index'))
            ->assertOk()
            ->assertSeeText('Promo Sehat')
            ->assertSeeText('Promo Aktif')
            ->assertDontSeeText('Promo Draf')
            ->assertDontSeeText('Promo Berakhir')
            ->assertSee('promotion-premium.css');
    }

    public function test_marketing_can_publish_a_promotion_and_patient_is_notified(): void
    {
        Storage::fake('public');
        Notification::fake();

        $marketing = $this->marketing();
        $patient = $this->patient();

        $response = $this->actingAs($marketing)->post(route('promotions.store'), [
            'title' => 'Paket Jantung Sehat',
            'caption' => 'Dapatkan pemeriksaan kesehatan jantung dengan pelayanan terbaik.',
            'image' => UploadedFile::fake()->image('jantung-sehat.jpg', 1200, 800),
            'starts_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'duration_value' => 2,
            'duration_unit' => 'day',
            'status' => Promotion::STATUS_PUBLISHED,
        ]);

        $response->assertRedirect(route('promotions.index'))->assertSessionHas('success');

        $promotion = Promotion::query()->where('title', 'Paket Jantung Sehat')->firstOrFail();
        Storage::disk('public')->assertExists($promotion->image_path);
        $this->assertNotNull($promotion->notified_at);
        $this->assertTrue($promotion->ends_at->equalTo($promotion->starts_at->copy()->addDays(2)));

        Notification::assertSentTo($patient, PromotionPublishedNotification::class);
    }

    public function test_future_promotion_waits_until_its_schedule_before_notifying_patients(): void
    {
        Storage::fake('public');
        Notification::fake();

        $marketing = $this->marketing();
        $patient = $this->patient();

        $this->actingAs($marketing)->post(route('promotions.store'), [
            'title' => 'Promo Bulan Depan',
            'caption' => 'Promosi yang sudah dijadwalkan untuk bulan depan.',
            'image' => UploadedFile::fake()->image('promo.jpg'),
            'starts_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'duration_value' => 1,
            'duration_unit' => 'month',
            'status' => Promotion::STATUS_PUBLISHED,
        ])->assertRedirect(route('promotions.index'));

        $this->assertNull(Promotion::query()->where('title', 'Promo Bulan Depan')->value('notified_at'));
        Notification::assertNotSentTo($patient, PromotionPublishedNotification::class);
    }

    public function test_patient_cannot_open_marketing_editor(): void
    {
        $this->actingAs($this->patient())
            ->get(route('promotions.create'))
            ->assertForbidden();
    }

    public function test_authenticated_user_can_manage_push_subscription(): void
    {
        config([
            'webpush.vapid.public_key' => 'public-test-key',
            'webpush.vapid.private_key' => 'private-test-key',
        ]);

        $patient = $this->patient();
        $payload = [
            'endpoint' => 'https://push.example.test/subscriptions/device-1',
            'keys' => ['p256dh' => 'public-browser-key', 'auth' => 'browser-token'],
            'content_encoding' => 'aes128gcm',
        ];

        $this->actingAs($patient)->postJson(route('push.store'), $payload)->assertCreated();
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $payload['endpoint'], 'subscribable_id' => $patient->id]);

        $this->deleteJson(route('push.destroy'), ['endpoint' => $payload['endpoint']])->assertOk();
        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $payload['endpoint']]);
    }

    private function patient(): User
    {
        $role = Role::findOrCreate(config('access-control.patient_role', 'Patient'), 'web');
        $role->givePermissionTo(['EPASIEN.MENU', 'EPASIEN.MENU.PROMOSI']);
        $user = User::factory()->create(['status' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function marketing(): User
    {
        $role = Role::findOrCreate(config('access-control.marketing_role', 'Marketing'), 'web');
        $role->givePermissionTo(['EPASIEN.MENU', 'EPASIEN.MENU.PROMOSI', 'EPASIEN.MENU.PROMOSI.KELOLA']);
        $user = User::factory()->create(['status' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function promotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'creator_id' => null,
            'title' => 'Promo Sehat',
            'caption' => 'Caption promosi kesehatan.',
            'image_path' => 'promotions/example.jpg',
            'duration_value' => 1,
            'duration_unit' => 'day',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'status' => Promotion::STATUS_PUBLISHED,
            'published_at' => now(),
        ], $attributes));
    }
}
