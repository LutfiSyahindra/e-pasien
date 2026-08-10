<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\User;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\menu\DoctorQueueService;
use App\Services\epasien\menu\JadwalDokterService;
use App\Services\epasien\menu\PromotionService;
use Illuminate\Support\Facades\Gate;
use Mockery\MockInterface;
use RuntimeException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PermissionMiddleware::class);
        Gate::shouldReceive('check')->zeroOrMoreTimes()->andReturnTrue();
        Gate::shouldReceive('any')->zeroOrMoreTimes()->andReturnTrue();
    }

    public function test_patient_dashboard_shows_latest_information_visit_and_today_schedules(): void
    {
        $user = $this->patientUser([
            'name' => 'Budi Santoso',
            'username' => '000123',
        ]);
        $promotion = new Promotion([
            'category' => Promotion::CATEGORY_INFORMATION,
            'title' => 'Layanan Poli Baru',
            'caption' => 'Informasi layanan poliklinik terbaru untuk pasien.',
            'image_path' => 'promotions/poli-baru.webp',
            'duration_value' => 7,
            'duration_unit' => 'day',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'status' => Promotion::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);
        $promotion->id = 1;
        $promotion->exists = true;
        $registration = [
            'status' => 'Belum',
            'status_tone' => 'warning',
            'poli' => 'Poliklinik Anak',
            'dokter' => 'dr. Achmad Yunus, Sp.A',
            'no_reg' => '007',
            'hari_short' => 'Jum',
            'tanggal_angka' => '07',
            'bulan_short' => 'Agu',
            'tanggal_lengkap' => 'Jumat, 7 Agustus 2026',
            'jam' => '08:00',
            'penjamin' => 'BPJS Kesehatan',
        ];
        $schedules = collect([[
            'doctor_code' => 'D001',
            'doctor_name' => 'dr. Siti Aminah, Sp.PD',
            'doctor_initials' => 'SA',
            'doctor_photo_url' => null,
            'clinic_name' => 'Poliklinik Penyakit Dalam',
            'time_label' => '08.00 – 11.00 WIB',
            'quota_label' => '30 pasien',
        ]]);
        $queues = collect([[
            'id' => 'queue-1',
            'doctor_code' => 'D001',
            'doctor_name' => 'dr. Achmad Yunus, Sp.A',
            'clinic_code' => 'ANA',
            'clinic_name' => 'Poliklinik Anak',
            'current_number' => '007',
            'last_serviced_number' => '006',
            'queue_state' => 'calling',
            'number_label' => 'Sedang dipanggil',
            'queue_message' => 'Panggilan antrean sedang berlangsung.',
            'serviced_at' => '2026-08-07T08:20:00+07:00',
            'serviced_at_label' => '08:20 WIB',
            'is_patient_queue' => true,
            'patient_number' => '007',
            'remaining_before_patient' => 0,
            'patient_message' => 'Antrean Anda sedang dipanggil. Silakan menuju poli sekarang.',
        ]]);

        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($user, $registration): void {
            $mock->shouldReceive('upcomingRegistration')
                ->once()
                ->with($user)
                ->andReturn($registration);
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock) use ($schedules): void {
            $mock->shouldReceive('today')->once()->with(6)->andReturn($schedules);
        });
        $this->mock(DoctorQueueService::class, function (MockInterface $mock) use ($registration, $queues): void {
            $mock->shouldReceive('current')->once()->with($registration)->andReturn($queues);
        });
        $this->mock(PromotionService::class, function (MockInterface $mock) use ($promotion): void {
            $mock->shouldReceive('latestActive')->once()->withArgs(
                fn ($at, int $limit): bool => $at->timezoneName === 'Asia/Jakarta' && $limit === 4
            )->andReturn(collect([$promotion]));
        });

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('e-pasien.dashboard')
            ->assertSeeText('Halo, Budi!')
            ->assertSeeText('Promosi & informasi terbaru')
            ->assertSeeText('Layanan Poli Baru')
            ->assertSeeText('Kunjungan terdekat')
            ->assertSeeText('Poliklinik Anak')
            ->assertSeeText('007')
            ->assertSeeText('Antrean poli sedang berjalan')
            ->assertSeeText('Sedang dipanggil')
            ->assertSeeText('Antrean Anda sedang dipanggil. Silakan menuju poli sekarang.')
            ->assertSeeText('Jadwal dokter hari ini')
            ->assertSeeText('dr. Siti Aminah, Sp.PD')
            ->assertSeeText('08.00 – 11.00 WIB')
            ->assertSee('class="patient-dashboard-mobile-index"', false)
            ->assertSeeText('Geser untuk melihat dokter lainnya')
            ->assertSee('patient-dashboard.css');
    }

    public function test_dashboard_keeps_rendering_when_khanza_sections_are_unavailable(): void
    {
        $user = $this->patientUser([
            'name' => 'Budi Santoso',
            'username' => '000123',
        ]);

        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('upcomingRegistration')
                ->once()
                ->andThrow(new RuntimeException('Khanza unavailable'));
        });
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('today')
                ->once()
                ->andThrow(new RuntimeException('Khanza unavailable'));
        });
        $this->mock(DoctorQueueService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('current')
                ->once()
                ->with(null)
                ->andThrow(new RuntimeException('Khanza unavailable'));
        });
        $this->mock(PromotionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('latestActive')->once()->andReturn(collect());
        });

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Agenda belum dapat dimuat')
            ->assertSeeText('Antrean belum dapat dimuat')
            ->assertSeeText('Jadwal belum dapat dimuat');
    }

    public function test_dashboard_queue_endpoint_returns_current_calls(): void
    {
        $user = $this->patientUser(['username' => '000123']);
        $registration = [
            'tanggal' => '2026-08-10',
            'kd_dokter' => 'D001',
            'kd_poli' => 'ANA',
            'no_reg' => '007',
        ];
        $queues = collect([[
            'id' => 'queue-1',
            'doctor_code' => 'D001',
            'clinic_code' => 'ANA',
            'current_number' => '005',
            'is_patient_queue' => true,
        ]]);

        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($user, $registration): void {
            $mock->shouldReceive('upcomingRegistration')
                ->once()
                ->with($user)
                ->andReturn($registration);
        });
        $this->mock(DoctorQueueService::class, function (MockInterface $mock) use ($registration, $queues): void {
            $mock->shouldReceive('current')->once()->with($registration)->andReturn($queues);
        });

        $this->actingAs($user)
            ->getJson(route('dashboard.doctorQueues'))
            ->assertOk()
            ->assertJsonPath('data.0.current_number', '005')
            ->assertJsonPath('data.0.is_patient_queue', true)
            ->assertJsonStructure(['meta' => ['refreshed_at', 'refreshed_at_label']]);
    }

    private function patientUser(array $attributes = []): User
    {
        $user = new User(array_merge([
            'name' => 'Budi Santoso',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ], $attributes));
        $user->id = 1;
        $user->setRelation('roles', collect());
        $user->setRelation('permissions', collect());

        return $user;
    }
}
