<?php

namespace Tests\Feature;

use App\Services\epasien\menu\JadwalDokterService;
use App\Services\LandingPage\LandingDoctorService;
use App\Support\Epasien\DoctorScheduleDay;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-12 08:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_landing_page_uses_clean_navigation_and_login_action(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')->once()->with('', 'SENIN', '', 6)->andReturn($this->schedulePage());
        });

        $this->get(route('landingPage.index'))
            ->assertOk()
            ->assertSee('aria-label="Navigasi utama"', false)
            ->assertSee('class="header-one header--sticky premium-navbar"', false)
            ->assertSee('<body class="landing-page">', false)
            ->assertSee('class="side-bar header-two premium-mobile-nav"', false)
            ->assertSee('premium-navbar__login', false)
            ->assertSee(route('landingPage.index').'#layanan', false)
            ->assertSee(route('landingPage.index').'#aplikasi', false)
            ->assertSee(route('landingPage.index').'#poli-spesialis', false)
            ->assertSee(route('landingPage.index').'#jadwal-praktik', false)
            ->assertSee(route('landingPage.index').'#dokter-spesialis', false)
            ->assertSee(route('landingPage.index').'#informasi-kontak', false)
            ->assertSee('href="'.route('login').'" class="rts-btn btn-primary"', false)
            ->assertSeeText('Login')
            ->assertSee('id="akses-epasien" class="patient-login-cta"', false)
            ->assertSeeText('Seluruh layanan E-Pasien, cukup satu kali login.')
            ->assertSeeText('Masuk ke E-Pasien')
            ->assertSee('href="'.route('login').'" class="patient-login-cta__button"', false)
            ->assertSee('<html lang="id">', false)
            ->assertSee('<title>RS ARSY | Pelayanan Kesehatan</title>', false)
            ->assertSee('class="landing-footer"', false)
            ->assertDontSee('placeholder="Search..."', false)
            ->assertDontSeeText('2702 Memory Lane')
            ->assertDontSeeText('Hospital Home')
            ->assertDontSeeText('Mediweb')
            ->assertDontSeeText('ThemeWant')
            ->assertDontSeeText('Pricing Plan')
            ->assertDontSeeText('Words from Our Patients')
            ->assertDontSeeText('Why Choose Us')
            ->assertDontSeeText('Canada, 245 14h Street');
    }

    public function test_landing_page_offers_pwa_installation_without_notification_permission_prompt(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')->once()->with('', 'SENIN', '', 6)->andReturn($this->schedulePage());
        });

        $this->get(route('landingPage.index'))
            ->assertOk()
            ->assertSee('id="aplikasi"', false)
            ->assertSee('data-pwa-section', false)
            ->assertSee('data-pwa-install', false)
            ->assertSee('class="landing-pwa-mobile"', false)
            ->assertSeeText('Install Aplikasi')
            ->assertSeeText('Halo, Sahabat Sehat!')
            ->assertSeeText('Kunjungan terdekat')
            ->assertSeeText('Tanpa Play Store')
            ->assertSee('rel="manifest"', false)
            ->assertSee('epasien/assets/js/pwa-install.js', false)
            ->assertDontSee('Notification.requestPermission', false);
    }

    public function test_landing_page_shows_today_doctor_schedules(): void
    {
        $schedules = collect([
            [
                'doctor_code' => 'D001',
                'doctor_name' => 'dr. Siti Aminah, Sp.PD',
                'doctor_initials' => 'SA',
                'doctor_photo_url' => '/storage/doctor-photos/d001.webp',
                'clinic_name' => 'Poliklinik Penyakit Dalam',
                'day' => 'SENIN',
                'day_label' => 'Senin',
                'time_label' => '08.00 - 11.00 WIB',
                'quota_label' => '30 pasien',
            ],
        ]);

        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock) use ($schedules): void {
            $mock->shouldReceive('page')->once()->with('', 'SENIN', '', 6)->andReturn($this->schedulePage($schedules));
        });

        $this->get(route('landingPage.index'))
            ->assertOk()
            ->assertViewIs('landingPage.landingPage')
            ->assertSee('id="jadwal-praktik"', false)
            ->assertSeeText('Temukan dokter yang praktik hari ini.')
            ->assertSeeText('Pilih hari kunjungan')
            ->assertSee('class="landing-doctor-schedule__day is-selected"', false)
            ->assertSee('class="landing-schedule-feature', false)
            ->assertSeeText('dr. Siti Aminah, Sp.PD')
            ->assertSeeText('Poliklinik Penyakit Dalam')
            ->assertSeeText('08.00 - 11.00 WIB')
            ->assertSeeText('Kuota layanan')
            ->assertSee('src="/storage/doctor-photos/d001.webp"', false)
            ->assertSee('landing-doctor-schedule');
    }

    public function test_landing_page_shows_hospital_contact_directory(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')->once()->with('', 'SENIN', '', 6)->andReturn($this->schedulePage());
        });

        $this->get(route('landingPage.index'))
            ->assertOk()
            ->assertSee('id="informasi-kontak"', false)
            ->assertSeeText('Informasi & Kontak')
            ->assertSeeText('Call Centre')
            ->assertSeeText('081232870119')
            ->assertSeeText('081358292709')
            ->assertSeeText('088297178737')
            ->assertSeeText('081359666323')
            ->assertSeeText('081232310793')
            ->assertSeeText('@rsarsy_official')
            ->assertSee('href="tel:+6281232870119"', false)
            ->assertSee(route('landingPage.index').'#informasi-kontak', false)
            ->assertSee('href="https://www.instagram.com/rsarsy_official/"', false)
            ->assertSeeText('Lokasi RS ARSY')
            ->assertSeeText('Jl. Raya Deandles KM 74, Paciran')
            ->assertSeeText('Navigasi ke RS ARSY')
            ->assertSee('title="Peta lokasi RS KH. Abdurrahman Syamsuri"', false)
            ->assertSee('https://www.google.com/maps?q=RS%20KH.%20Abdurrahman%20Syamsuri%20RS%20ARSY%20Paciran%20Lamongan', false)
            ->assertSee('destination_place_id=ChIJyevd-PLBdy4R2DwQysLmRDM', false);
    }

    public function test_landing_page_shows_featured_specialist_doctors(): void
    {
        $doctor = [
            'doctor_code' => 'D001',
            'doctor_name' => 'dr. Achmad Yunus, Sp.A',
            'photo_url' => '/storage/doctor-photos/d001.webp',
        ];

        $this->mock(LandingDoctorService::class, function (MockInterface $mock) use ($doctor): void {
            $mock->shouldReceive('featured')->once()->andReturn([$doctor]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')->once()->with('', 'SENIN', '', 6)->andReturn($this->schedulePage());
        });

        $this->get(route('landingPage.index'))
            ->assertOk()
            ->assertSee('id="dokter-spesialis"', false)
            ->assertSeeText('Dokter Spesialis RS ARSY')
            ->assertSeeText('Tenaga ahli untuk setiap langkah pemulihan.')
            ->assertSee('class="landing-specialist-card is-tone-1"', false)
            ->assertSeeText('Geser untuk melihat poli spesialis lainnya')
            ->assertSeeText($doctor['doctor_name'])
            ->assertSeeText('Sp.A')
            ->assertSee('src="/storage/doctor-photos/d001.webp"', false)
            ->assertSee(e(route('landingPage.index', ['hari' => 'SEMUA', 'q' => $doctor['doctor_name']])).'#jadwal-praktik', false)
            ->assertDontSee(route('jadwalDokter.index'), false)
            ->assertDontSeeText('Dr. Rachel Evans');
    }

    public function test_landing_page_stays_available_when_schedule_source_is_unavailable(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')
                ->once()
                ->with('', 'SENIN', '', 6)
                ->andThrow(new RuntimeException('Khanza unavailable'));
        });

        $this->get(route('landingPage.index'))
            ->assertOk()
            ->assertSeeText('Jadwal belum dapat dimuat')
            ->assertSeeText('Koneksi data rumah sakit sedang diperbarui.');
    }

    #[DataProvider('publicScheduleDays')]
    public function test_guest_can_select_tuesday_through_sunday_on_landing_page(string $day, string $label): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock) use ($day, $label): void {
            $mock->shouldReceive('page')->once()->with('', $day, '', 6)->andReturn($this->schedulePage(collect([
                $this->schedule(['day' => $day, 'day_label' => $label]),
            ])));
        });

        $response = $this->get(route('landingPage.index', ['hari' => $day]))
            ->assertOk()
            ->assertSeeText('Jadwal hari '.$label)
            ->assertSeeText('dr. Sehat')
            ->assertSeeText('08.00 - 10.00 WIB')
            ->assertDontSee(route('jadwalDokter.index'), false)
            ->assertViewHas('landingScheduleDays', fn (Collection $days): bool => $days->where('is_selected', true)->pluck('key')->all() === [$day]);

        foreach (DoctorScheduleDay::EPASIEN_DAYS as $scheduleDay) {
            $response->assertSee('href="'.route('landingPage.index', ['hari' => $scheduleDay]).'#jadwal-praktik"', false);
        }

        $this->assertGuest();
    }

    public static function publicScheduleDays(): array
    {
        return [
            ['SELASA', 'Selasa'],
            ['RABU', 'Rabu'],
            ['KAMIS', 'Kamis'],
            ['JUMAT', 'Jumat'],
            ['SABTU', 'Sabtu'],
            ['MINGGU', 'Minggu'],
        ];
    }

    public function test_guest_can_view_all_days_and_filter_a_doctor_without_login(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')->once()->with('dr. Sehat', 'SEMUA', '', 6)->andReturn($this->schedulePage(collect([
                $this->schedule(['day' => 'SELASA', 'day_label' => 'Selasa']),
                $this->schedule(['day' => 'MINGGU', 'day_label' => 'Minggu']),
            ])));
        });

        $this->get(route('landingPage.index', ['hari' => 'SEMUA', 'q' => 'dr. Sehat']))
            ->assertOk()
            ->assertSeeText('Jadwal semua hari')
            ->assertSeeText('Jam praktik Selasa')
            ->assertSeeText('Jam praktik Minggu');

        $this->assertGuest();
    }

    public function test_guest_can_view_more_schedules_without_losing_day_and_doctor_filters(): void
    {
        $paginator = new LengthAwarePaginator(collect([$this->schedule()]), 13, 6, 2, ['path' => route('landingPage.index')]);
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock) use ($paginator): void {
            $mock->shouldReceive('page')->once()->with('dr. Sehat', 'SELASA', '', 6)->andReturn(['schedules' => $paginator]);
        });

        $this->get(route('landingPage.index', ['hari' => 'SELASA', 'q' => 'dr. Sehat', 'page' => 2]))
            ->assertOk()
            ->assertSeeText('Halaman 2 dari 3')
            ->assertSee('href="'.e(route('landingPage.index', ['hari' => 'SELASA', 'q' => 'dr. Sehat', 'page' => 1])).'#jadwal-praktik" rel="prev"', false)
            ->assertSee('href="'.e(route('landingPage.index', ['hari' => 'SELASA', 'q' => 'dr. Sehat', 'page' => 3])).'#jadwal-praktik" rel="next"', false)
            ->assertSee('href="'.route('landingPage.index', ['hari' => 'MINGGU']).'#jadwal-praktik"', false);
    }

    public function test_empty_schedules_describe_the_selected_day(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')->once()->with('', 'MINGGU', '', 6)->andReturn($this->schedulePage());
        });

        $this->get(route('landingPage.index', ['hari' => 'MINGGU']))
            ->assertOk()
            ->assertSeeText('Belum ada jadwal praktik hari Minggu')
            ->assertDontSeeText('Belum ada jadwal praktik hari ini');
    }

    public function test_leave_status_uses_the_selected_visit_date(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('page')->once()->with('', 'SELASA', '', 6)->andReturn($this->schedulePage(collect([
                $this->schedule(['is_on_leave' => false, 'leave_dates' => ['2026-10-13']]),
            ])));
        });

        $this->get(route('landingPage.index', ['hari' => 'SELASA']))
            ->assertOk()
            ->assertSeeText('Dokter libur')
            ->assertDontSeeText('08.00 - 10.00 WIB');
    }

    public function test_invalid_schedule_day_is_rejected_before_loading_schedules(): void
    {
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('page');
        });

        $this->from(route('landingPage.index'))
            ->get(route('landingPage.index', ['hari' => 'LIBUR']))
            ->assertRedirect(route('landingPage.index'))
            ->assertSessionHasErrors('hari');
    }

    private function schedulePage(?Collection $schedules = null): array
    {
        $schedules ??= collect();

        return ['schedules' => new LengthAwarePaginator($schedules, $schedules->count(), 6, 1, ['path' => route('landingPage.index')])];
    }

    private function schedule(array $changes = []): array
    {
        return [
            'doctor_code' => 'D001',
            'doctor_name' => 'dr. Sehat',
            'doctor_initials' => 'S',
            'doctor_photo_url' => null,
            'clinic_name' => 'Poliklinik Penyakit Dalam',
            'day' => 'SELASA',
            'day_label' => 'Selasa',
            'time_label' => '08.00 - 10.00 WIB',
            'quota_label' => '20 pasien',
            ...$changes,
        ];
    }
}
