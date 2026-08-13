<?php

namespace Tests\Feature;

use App\Services\epasien\menu\JadwalDokterService;
use App\Services\LandingPage\LandingDoctorService;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_uses_clean_navigation_and_login_action(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('today')->once()->with(6)->andReturn(collect());
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
            $mock->shouldReceive('today')->once()->with(6)->andReturn(collect());
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
                'day' => 'KAMIS',
                'time_label' => '08.00 - 11.00 WIB',
                'quota_label' => '30 pasien',
            ],
        ]);

        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock) use ($schedules): void {
            $mock->shouldReceive('today')->once()->with(6)->andReturn($schedules);
        });

        $this->get(route('landingPage.index'))
            ->assertOk()
            ->assertViewIs('landingPage.landingPage')
            ->assertSee('id="jadwal-praktik"', false)
            ->assertSeeText('Temukan dokter yang praktik hari ini.')
            ->assertSeeText('Pilih hari kunjungan')
            ->assertSee('class="landing-doctor-schedule__day is-today"', false)
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
            $mock->shouldReceive('today')->once()->with(6)->andReturn(collect());
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
            $mock->shouldReceive('today')->once()->with(6)->andReturn(collect());
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
            ->assertSee(route('jadwalDokter.index', ['q' => $doctor['doctor_name']]), false)
            ->assertDontSeeText('Dr. Rachel Evans');
    }

    public function test_landing_page_stays_available_when_schedule_source_is_unavailable(): void
    {
        $this->mock(LandingDoctorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('featured')->once()->andReturn([]);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('today')
                ->once()
                ->with(6)
                ->andThrow(new RuntimeException('Khanza unavailable'));
        });

        $this->get(route('landingPage.index'))
            ->assertOk()
            ->assertSeeText('Jadwal belum dapat dimuat')
            ->assertSeeText('Koneksi data rumah sakit sedang diperbarui.');
    }
}
