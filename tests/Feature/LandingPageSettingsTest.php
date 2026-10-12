<?php

namespace Tests\Feature;

use App\Models\LandingPageSetting;
use App\Models\User;
use App\Services\epasien\menu\JadwalDokterService;
use App\Services\LandingPage\LandingDoctorService;
use App\Services\LandingPage\LandingPageContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LandingPageSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('EPASIEN.SETTINGS', 'web');
    }

    public function test_guests_cannot_view_or_update_landing_page_settings(): void
    {
        $this->get(route('landingPageSettings.edit'))->assertRedirect(route('login'));
        $this->put(route('landingPageSettings.update'), ['content' => $this->defaults()])->assertRedirect(route('login'));
        $this->assertDatabaseCount('landing_page_settings', 0);
    }

    public function test_settings_permission_alone_does_not_allow_editing_public_content(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('EPASIEN.SETTINGS');

        $this->actingAs($user)->get(route('landingPageSettings.edit'))->assertForbidden();
        $this->put(route('landingPageSettings.update'), ['content' => $this->defaults()])->assertForbidden();
        $this->assertDatabaseCount('landing_page_settings', 0);
    }

    public function test_authorized_editor_can_view_defaults_and_navigation_without_doctor_permission(): void
    {
        $this->actingAs($this->editor())->get(route('landingPageSettings.edit'))
            ->assertOk()
            ->assertSeeText('Informasi Landing Page')
            ->assertSee('name="content[hero][title]"', false)
            ->assertSee('name="content[clinics][0][name]"', false)
            ->assertSee('name="images[hero]"', false)
            ->assertSee('landing-page-settings.js')
            ->assertSee(route('landingPageSettings.edit'), false)
            ->assertDontSee(route('doctorPhotoSettings.index'), false);

        $this->assertDatabaseCount('landing_page_settings', 0);
    }

    public function test_saved_content_is_persisted_and_displayed_consistently_to_guests(): void
    {
        $user = $this->editor();
        $content = $this->defaults();
        $content['hero']['title'] = 'Sahabat kesehatan keluarga';
        $content['identity']['short_name'] = 'RS Sahabat';
        $content['contact']['call_number'] = '0812 3456 7890';
        $content['contact']['emergency_number'] = '+62 813 1111 2222';
        $content['contact']['social_url'] = 'https://www.instagram.com/rs_sahabat/';
        $content['contact']['social_handle'] = '@rs_sahabat';
        $content['location']['address'] = 'Jalan Sehat Nomor 10';
        $content['services'] = [
            ['name' => 'Layanan Unggulan Baru', 'category' => 'Layanan Pasien', 'icon' => 'fa-hospital'],
        ];
        $content['clinics'] = [
            ['name' => 'Poli Baru', 'focus' => 'Kesehatan', 'description' => 'Konsultasi keluarga.', 'icon' => 'fa-heart', 'group' => 'family'],
        ];
        $content['other_services'] = [];
        $content['additional_contacts'] = [
            ['label' => 'Unit Baru', 'number' => '031 12345678', 'icon' => 'fa-headset', 'tone' => 'primary'],
        ];

        $this->actingAs($user)->put(route('landingPageSettings.update'), ['content' => $content])
            ->assertRedirect(route('landingPageSettings.edit'))
            ->assertSessionHas('status');

        $setting = LandingPageSetting::query()->sole();
        $this->assertSame($user->id, $setting->updated_by);
        $this->assertSame($content['services'], $setting->content['services']);
        $this->assertSame([], $setting->content['other_services']);

        auth()->forgetGuards();
        $this->mockPublicData();
        $this->get(route('landingPage.index'))->assertOk()
            ->assertSeeText('Sahabat kesehatan keluarga')
            ->assertSeeText('RS Sahabat')
            ->assertSeeText('Jalan Sehat Nomor 10')
            ->assertSeeText('Layanan Unggulan Baru')
            ->assertSeeText('Poli Baru')
            ->assertSeeText('Unit Baru')
            ->assertSee('href="tel:+6281234567890"', false)
            ->assertSee('href="tel:+6281311112222"', false)
            ->assertSee('href="tel:+623112345678"', false)
            ->assertSee('href="https://www.instagram.com/rs_sahabat/"', false)
            ->assertDontSeeText('081232870119')
            ->assertDontSeeText('Poli Anak')
            ->assertDontSeeText('Medical Check-Up');
    }

    public function test_form_can_clear_optional_collections_and_update_existing_setting(): void
    {
        $content = $this->defaults();
        $this->actingAs($this->editor())->put(route('landingPageSettings.update'), ['content' => $content])->assertSessionHasNoErrors();
        unset($content['other_services'], $content['additional_contacts']);
        $content['hero']['title'] = 'Judul diperbarui';

        $this->put(route('landingPageSettings.update'), ['content' => $content])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('landing_page_settings', 1);
        $saved = LandingPageSetting::query()->sole()->content;
        $this->assertSame([], $saved['other_services']);
        $this->assertSame([], $saved['additional_contacts']);
        $this->assertSame('Judul diperbarui', $saved['hero']['title']);
    }

    #[DataProvider('invalidContent')]
    public function test_invalid_content_is_rejected_without_changing_saved_information(string $path, mixed $value): void
    {
        $content = $this->defaults();
        $this->actingAs($this->editor())->put(route('landingPageSettings.update'), ['content' => $content])->assertSessionHasNoErrors();
        $previous = LandingPageSetting::query()->sole()->content;
        data_set($content, $path, $value);

        $this->from(route('landingPageSettings.edit'))->put(route('landingPageSettings.update'), ['content' => $content])
            ->assertRedirect(route('landingPageSettings.edit'))
            ->assertSessionHasErrors('content.'.$path);

        $this->assertSame($previous, LandingPageSetting::query()->sole()->content);
    }

    public static function invalidContent(): array
    {
        return [
            'empty title' => ['hero.title', ''],
            'invalid phone' => ['contact.call_number', 'bukan nomor telepon'],
            'phone without sufficient digits' => ['contact.call_number', '1-----'],
            'unsafe social URL' => ['contact.social_url', 'javascript:alert(1)'],
            'external map iframe' => ['location.embed_url', 'https://example.com/maps'],
            'deceptive map host' => ['location.embed_url', 'https://www.google.com.evil.test/maps'],
            'no main service' => ['services', []],
            'associative service collection' => ['services', ['wrong' => ['name' => 'Layanan', 'category' => 'Umum', 'icon' => 'fa-hospital']]],
            'invalid icon' => ['services.0.icon', '" onclick="alert(1)'],
            'invalid clinic group' => ['clinics.0.group', 'unknown'],
            'too many items' => ['services', array_fill(0, 31, ['name' => 'Layanan', 'category' => 'Umum', 'icon' => 'fa-hospital'])],
        ];
    }

    public function test_html_in_content_is_escaped_on_the_public_page(): void
    {
        $content = $this->defaults();
        $content['hero']['title'] = '<script>alert("test")</script>';
        $this->actingAs($this->editor())->put(route('landingPageSettings.update'), ['content' => $content])->assertSessionHasNoErrors();

        $this->mockPublicData();
        $this->get(route('landingPage.index'))->assertOk()
            ->assertSee(e($content['hero']['title']), false)
            ->assertDontSee($content['hero']['title'], false);
    }

    public function test_editor_can_upload_replace_and_reset_images(): void
    {
        Storage::fake('public');
        $content = $this->defaults();
        $this->actingAs($this->editor())->put(route('landingPageSettings.update'), [
            'content' => $content,
            'images' => ['hero' => UploadedFile::fake()->image('banner.jpg', 1200, 600)],
        ])->assertSessionHasNoErrors();
        $firstPath = LandingPageSetting::query()->sole()->images['hero'];
        Storage::disk('public')->assertExists($firstPath);
        $this->assertSame(Storage::disk('public')->url($firstPath), app(LandingPageContentService::class)->current()['landingImages']['hero']);

        $this->put(route('landingPageSettings.update'), [
            'content' => $content,
            'images' => ['logo' => null, 'hero' => null, 'building' => null],
        ])->assertSessionHasNoErrors();
        $this->assertSame($firstPath, LandingPageSetting::query()->sole()->images['hero']);

        $this->put(route('landingPageSettings.update'), [
            'content' => $content,
            'images' => ['hero' => UploadedFile::fake()->image('banner-baru.png', 1000, 500)],
        ])->assertSessionHasNoErrors();
        $newPath = LandingPageSetting::query()->sole()->images['hero'];
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($newPath);

        $this->put(route('landingPageSettings.update'), ['content' => $content, 'reset_images' => ['hero' => 1]])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($newPath);
        $this->assertNull(LandingPageSetting::query()->sole()->images['hero']);
        $this->assertSame(asset(config('landing-page.images.hero.default')), app(LandingPageContentService::class)->current()['landingImages']['hero']);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs($this->editor())->put(route('landingPageSettings.update'), [
            'content' => $this->defaults(),
            'images' => ['logo' => UploadedFile::fake()->create('logo.svg', 2, 'image/svg+xml')],
        ])->assertSessionHasErrors('images.logo');

        $this->assertDatabaseCount('landing_page_settings', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_unrecognized_upload_fields_are_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs($this->editor())->put(route('landingPageSettings.update'), [
            'content' => $this->defaults(),
            'images' => ['unknown' => UploadedFile::fake()->create('document.pdf', 2, 'application/pdf')],
        ])->assertSessionHasErrors('images');

        $this->assertDatabaseCount('landing_page_settings', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function editor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['EPASIEN.SETTINGS', 'EPASIEN.SETTINGS.LANDING_PAGE']);

        return $user;
    }

    private function defaults(): array
    {
        return app(LandingPageContentService::class)->defaults();
    }

    private function mockPublicData(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')->once()->andReturn([
                'schedules' => new LengthAwarePaginator([], 0, 6, 1, ['path' => route('landingPage.index')]),
            ]);
        });
    }
}
