<?php

namespace Tests\Feature;

use App\Jobs\DispatchPromotionNotifications;
use App\Models\Promotion;
use App\Models\PromotionConfiguration;
use App\Models\PromotionNotificationUserConfiguration;
use App\Models\User;
use App\Notifications\PromotionPublishedNotification;
use App\Services\epasien\menu\PromotionNotificationRecipientService;
use App\Services\epasien\menu\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            ->assertSeeText('Promosi & Informasi')
            ->assertSeeText('Promo Aktif')
            ->assertDontSeeText('Periode tayang')
            ->assertDontSeeText('Hingga')
            ->assertDontSeeText('Berakhir')
            ->assertDontSeeText('Promo Draf')
            ->assertDontSeeText('Promo Berakhir')
            ->assertSee('promotion-premium.css');
    }

    public function test_patient_can_distinguish_and_filter_promotions_and_information(): void
    {
        $patient = $this->patient();
        $promotion = $this->promotion([
            'title' => 'Paket Pemeriksaan Hemat',
            'category' => Promotion::CATEGORY_PROMOTION,
        ]);
        $information = $this->promotion([
            'title' => 'Perubahan Jadwal Poliklinik',
            'category' => Promotion::CATEGORY_INFORMATION,
        ]);

        $this->actingAs($patient)
            ->get(route('promotions.index'))
            ->assertOk()
            ->assertSeeText($promotion->title)
            ->assertSeeText($information->title)
            ->assertSee('promo-patient-card__badge is-promotion', false)
            ->assertSee('promo-patient-card__badge is-information', false);

        $this->actingAs($patient)
            ->get(route('promotions.index', ['category' => Promotion::CATEGORY_INFORMATION]))
            ->assertOk()
            ->assertSeeText('Informasi')
            ->assertSeeText($information->title)
            ->assertDontSeeText($promotion->title);

        $this->actingAs($patient)
            ->get(route('promotions.show', $information))
            ->assertOk()
            ->assertSeeText('Informasi kesehatan')
            ->assertDontSeeText('Periode tayang')
            ->assertSee('data-promo-image-open', false)
            ->assertSee('data-promo-image-viewer', false)
            ->assertSee('promotion-image-viewer.js');

        $this->actingAs($this->marketing())
            ->get(route('promotions.show', $information))
            ->assertOk()
            ->assertSeeText('Periode tayang');

        $notification = PromotionPublishedNotification::fromPromotion($information);
        $this->assertSame('Informasi: '.$information->title, $notification->toArray($patient)['title']);
    }

    public function test_marketing_can_publish_a_promotion_and_patient_is_notified(): void
    {
        Storage::fake('public');
        Notification::fake();

        $marketing = $this->marketing();
        $patient = $this->patient();

        $response = $this->actingAs($marketing)->post(route('promotions.store'), [
            'category' => Promotion::CATEGORY_PROMOTION,
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
        $this->assertSame(Promotion::CATEGORY_PROMOTION, $promotion->category);
        Storage::disk('public')->assertExists($promotion->image_path);
        $this->assertSame('/storage/'.$promotion->image_path, $promotion->image_url);
        $this->assertNotNull($promotion->notified_at);
        $this->assertTrue($promotion->ends_at->equalTo($promotion->starts_at->copy()->addDays(2)));

        Notification::assertSentTo(
            $patient,
            PromotionPublishedNotification::class,
            fn (PromotionPublishedNotification $notification): bool => $notification->promotion['image_url'] === '/storage/'.$promotion->image_path
                && $notification->promotion['url'] === '/e-pasien/menu/promo-sehat/'.$promotion->getKey(),
        );
    }

    public function test_marketing_can_publish_information_with_an_information_notification(): void
    {
        Storage::fake('public');
        Notification::fake();

        $marketing = $this->marketing();
        $patient = $this->patient();

        $this->actingAs($marketing)->post(route('promotions.store'), [
            'category' => Promotion::CATEGORY_INFORMATION,
            'title' => 'Perubahan Jadwal Poli Anak',
            'caption' => 'Poli Anak buka mulai pukul 09.00 WIB selama masa libur.',
            'image' => UploadedFile::fake()->image('jadwal-poli-anak.jpg', 1200, 800),
            'starts_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'duration_value' => 2,
            'duration_unit' => 'day',
            'status' => Promotion::STATUS_PUBLISHED,
        ])->assertRedirect(route('promotions.index'));

        $information = Promotion::query()->where('title', 'Perubahan Jadwal Poli Anak')->firstOrFail();
        $this->assertSame(Promotion::CATEGORY_INFORMATION, $information->category);

        Notification::assertSentTo(
            $patient,
            PromotionPublishedNotification::class,
            fn (PromotionPublishedNotification $notification): bool => $notification->promotion['category'] === Promotion::CATEGORY_INFORMATION
                && $notification->toArray($patient)['title'] === 'Informasi: Perubahan Jadwal Poli Anak',
        );
    }

    public function test_future_promotion_waits_until_its_schedule_before_notifying_patients(): void
    {
        Storage::fake('public');
        Notification::fake();

        $marketing = $this->marketing();
        $patient = $this->patient();

        $this->actingAs($marketing)->post(route('promotions.store'), [
            'category' => Promotion::CATEGORY_PROMOTION,
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

    public function test_notification_center_uses_current_relative_promotion_links(): void
    {
        Storage::fake('public');

        $patient = $this->patient();
        $promotion = $this->promotion(['image_path' => 'promotions/navbar.jpg']);
        Storage::disk('public')->put($promotion->image_path, 'image-content');
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => PromotionPublishedNotification::class,
            'notifiable_type' => $patient->getMorphClass(),
            'notifiable_id' => $patient->getKey(),
            'data' => [
                'kind' => 'promotion',
                'title' => 'Promo lama',
                'promotion_id' => $promotion->getKey(),
                'image_url' => 'http://localhost/storage/old-image.jpg',
                'url' => 'http://localhost/e-pasien/menu/promo-sehat/'.$promotion->getKey(),
            ],
        ]);

        $this->actingAs($patient)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('notifications.0.data.image_url', '/storage/'.$promotion->image_path)
            ->assertJsonPath('notifications.0.data.url', '/e-pasien/menu/promo-sehat/'.$promotion->getKey());
    }

    public function test_notification_center_safely_handles_a_deleted_promotion(): void
    {
        $patient = $this->patient();
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => PromotionPublishedNotification::class,
            'notifiable_type' => $patient->getMorphClass(),
            'notifiable_id' => $patient->getKey(),
            'data' => [
                'kind' => 'promotion',
                'title' => 'Promo sudah dihapus',
                'promotion_id' => 999999,
                'image_url' => 'http://localhost/storage/missing.jpg',
                'url' => 'http://localhost/e-pasien/menu/promo-sehat/999999',
            ],
        ]);

        $this->actingAs($patient)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('notifications.0.data.image_url', null)
            ->assertJsonPath('notifications.0.data.url', '/e-pasien/menu/promo-sehat');
    }

    public function test_promotion_badge_count_follows_notification_read_state(): void
    {
        $patient = $this->patient();
        $first = $this->promotion(['title' => 'Promo Pertama']);
        $second = $this->promotion(['title' => 'Informasi Kedua', 'category' => Promotion::CATEGORY_INFORMATION]);

        $firstNotification = $this->promotionDatabaseNotification($patient, $first);
        $this->promotionDatabaseNotification($patient, $second);

        $this->actingAs($patient)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('promotion_unread_count', 2);

        $this->patchJson(route('notifications.read', $firstNotification))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('promotion_unread_count', 1);

        $this->patchJson(route('notifications.readAll'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('promotion_unread_count', 0);
    }

    public function test_opening_patient_promotion_pages_marks_matching_notifications_as_read(): void
    {
        $patient = $this->patient();
        $first = $this->promotion(['title' => 'Promo Dibuka']);
        $second = $this->promotion(['title' => 'Informasi Belum Dibuka', 'category' => Promotion::CATEGORY_INFORMATION]);
        $firstNotification = $this->promotionDatabaseNotification($patient, $first);
        $secondNotification = $this->promotionDatabaseNotification($patient, $second);

        $this->actingAs($patient)
            ->get(route('promotions.show', $first))
            ->assertOk();

        $this->assertNotNull($firstNotification->refresh()->read_at);
        $this->assertNull($secondNotification->refresh()->read_at);

        $this->actingAs($patient)
            ->get(route('promotions.index'))
            ->assertOk()
            ->assertSee('data-promotion-sidebar-badge', false);

        $this->assertNotNull($secondNotification->refresh()->read_at);
    }

    public function test_promotion_can_be_limited_to_explicit_test_users(): void
    {
        Notification::fake();

        $target = $this->patient();
        $otherPatient = $this->patient();
        $target->roles()->update(['promotion_notifications_enabled' => false]);
        PromotionNotificationUserConfiguration::query()->create([
            'user_id' => $target->id,
            'configured_by' => null,
        ]);
        $promotion = $this->promotion(['title' => 'Promo Target Pengujian']);

        (new DispatchPromotionNotifications($promotion->id))
            ->handle(app(PromotionNotificationRecipientService::class));

        Notification::assertSentTo($target, PromotionPublishedNotification::class);
        Notification::assertNotSentTo($otherPatient, PromotionPublishedNotification::class);
    }

    public function test_patient_cannot_open_marketing_editor(): void
    {
        $this->actingAs($this->patient())
            ->get(route('promotions.create'))
            ->assertForbidden();
    }

    public function test_marketing_can_configure_default_duration_and_expired_cleanup(): void
    {
        $marketing = $this->marketing();

        $this->actingAs($marketing)
            ->put(route('promotions.configuration.update'), [
                'default_duration_value' => 5,
                'default_duration_unit' => 'day',
                'auto_delete_enabled' => 1,
                'delete_grace_value' => 2,
                'delete_grace_unit' => 'hour',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('promotion_configurations', [
            'key' => PromotionConfiguration::DEFAULT_KEY,
            'default_duration_value' => 5,
            'default_duration_unit' => 'day',
            'auto_delete_enabled' => true,
            'delete_grace_value' => 2,
            'delete_grace_unit' => 'hour',
            'configured_by' => $marketing->id,
        ]);

        $this->actingAs($marketing)
            ->get(route('promotions.create'))
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSeeText('Promosi')
            ->assertSeeText('Informasi')
            ->assertViewHas('promotion', fn (Promotion $promotion): bool => $promotion->duration_value === 5
                && $promotion->duration_unit === 'day');
    }

    public function test_patient_cannot_open_promotion_configuration(): void
    {
        $this->actingAs($this->patient())
            ->get(route('promotions.configuration.edit'))
            ->assertForbidden();
    }

    public function test_expired_cleanup_deletes_published_and_archived_promotions_with_their_images(): void
    {
        Storage::fake('public');

        $patient = $this->patient();
        $expired = $this->promotion([
            'title' => 'Promo Kedaluwarsa',
            'image_path' => 'promotions/expired.jpg',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subMinute(),
        ]);
        $archived = $this->promotion([
            'title' => 'Promo Arsip Kedaluwarsa',
            'image_path' => 'promotions/archived.jpg',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subMinute(),
            'status' => Promotion::STATUS_ARCHIVED,
        ]);
        $draft = $this->promotion([
            'title' => 'Draf Terlambat',
            'image_path' => 'promotions/draft.jpg',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subMinute(),
            'status' => Promotion::STATUS_DRAFT,
        ]);
        $active = $this->promotion([
            'title' => 'Promo Aktif',
            'image_path' => 'promotions/active.jpg',
        ]);

        foreach ([$expired, $archived, $draft, $active] as $promotion) {
            Storage::disk('public')->put($promotion->image_path, 'image-content');
        }
        $notificationId = (string) Str::uuid();
        DatabaseNotification::query()->create([
            'id' => $notificationId,
            'type' => PromotionPublishedNotification::class,
            'notifiable_type' => $patient->getMorphClass(),
            'notifiable_id' => $patient->getKey(),
            'data' => [
                'kind' => 'promotion',
                'promotion_id' => $expired->getKey(),
                'image_url' => '/storage/'.$expired->image_path,
                'url' => '/e-pasien/menu/promo-sehat/'.$expired->getKey(),
            ],
        ]);

        $this->assertSame(2, app(PromotionService::class)->deleteExpired());

        $this->assertDatabaseMissing('promotions', ['id' => $expired->id]);
        $this->assertDatabaseMissing('promotions', ['id' => $archived->id]);
        Storage::disk('public')->assertMissing($expired->image_path);
        Storage::disk('public')->assertMissing($archived->image_path);
        $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);

        $this->assertDatabaseHas('promotions', ['id' => $draft->id]);
        $this->assertDatabaseHas('promotions', ['id' => $active->id]);
        Storage::disk('public')->assertExists($draft->image_path);
        Storage::disk('public')->assertExists($active->image_path);
    }

    public function test_expired_cleanup_honors_the_configured_grace_period(): void
    {
        Storage::fake('public');

        PromotionConfiguration::current()->update([
            'delete_grace_value' => 2,
            'delete_grace_unit' => 'day',
        ]);
        $old = $this->promotion([
            'image_path' => 'promotions/old.jpg',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDays(3),
        ]);
        $recent = $this->promotion([
            'image_path' => 'promotions/recent.jpg',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);
        Storage::disk('public')->put($old->image_path, 'image-content');
        Storage::disk('public')->put($recent->image_path, 'image-content');

        $this->assertSame(1, app(PromotionService::class)->deleteExpired());

        $this->assertDatabaseMissing('promotions', ['id' => $old->id]);
        Storage::disk('public')->assertMissing($old->image_path);
        $this->assertDatabaseHas('promotions', ['id' => $recent->id]);
        Storage::disk('public')->assertExists($recent->image_path);
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

    public function test_existing_browser_push_subscription_moves_to_the_current_user(): void
    {
        config([
            'webpush.vapid.public_key' => 'public-test-key',
            'webpush.vapid.private_key' => 'private-test-key',
        ]);

        $patient = $this->patient();
        $admin = User::factory()->create(['status' => true]);
        $payload = [
            'endpoint' => 'https://push.example.test/subscriptions/shared-browser',
            'keys' => ['p256dh' => 'public-browser-key', 'auth' => 'browser-token'],
            'content_encoding' => 'aes128gcm',
        ];

        $this->actingAs($patient)
            ->postJson(route('push.store'), $payload)
            ->assertCreated();

        $this->actingAs($admin)
            ->postJson(route('push.store'), $payload)
            ->assertCreated();

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
            'subscribable_id' => $patient->id,
        ]);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
            'subscribable_id' => $admin->id,
        ]);
        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    private function patient(): User
    {
        $role = Role::findOrCreate(config('access-control.patient_role', 'Patient'), 'web');
        $role->forceFill(['promotion_notifications_enabled' => true])->save();
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
            'category' => Promotion::CATEGORY_PROMOTION,
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

    private function promotionDatabaseNotification(User $patient, Promotion $promotion): DatabaseNotification
    {
        return DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => PromotionPublishedNotification::class,
            'notifiable_type' => $patient->getMorphClass(),
            'notifiable_id' => $patient->getKey(),
            'data' => PromotionPublishedNotification::fromPromotion($promotion)->toArray($patient),
        ]);
    }
}
