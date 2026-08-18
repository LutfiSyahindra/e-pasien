<?php

namespace Tests\Feature;

use App\Models\OnlineRegistrationAudit;
use App\Models\PatientServiceConversation;
use App\Models\User;
use App\Services\epasien\AdminDashboardService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Gate;
use Mockery\MockInterface;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PermissionMiddleware::class);
        Gate::shouldReceive('check')->zeroOrMoreTimes()->andReturnTrue();
        Gate::shouldReceive('any')->zeroOrMoreTimes()->andReturnTrue();
    }

    public function test_administrator_sees_the_operational_dashboard_with_summary_data(): void
    {
        $admin = $this->administrator();
        $patient = $this->user([
            'id' => 2,
            'name' => 'Budi Santoso',
            'username' => '000123',
        ]);
        $registration = new OnlineRegistrationAudit([
            'no_rawat' => '2026/08/18/000001',
            'no_reg' => '007',
            'registration_date' => '2026-08-18',
            'registration_time' => '10:00:00',
            'patient_medical_record_number' => '000123',
            'patient_name' => 'Budi Santoso',
            'doctor_code' => 'D001',
            'doctor_name' => 'dr. Sehat',
            'clinic_code' => 'INT',
            'clinic_name' => 'Poliklinik Penyakit Dalam',
            'guarantor_code' => 'BPJ',
            'guarantor_name' => 'BPJS Kesehatan',
            'registered_by_name' => 'Budi Santoso',
        ]);
        $conversation = new PatientServiceConversation([
            'patient_id' => 2,
            'category' => 'question',
            'subject' => 'Informasi jadwal kontrol',
            'status' => PatientServiceConversation::STATUS_WAITING_ADMIN,
            'last_message_at' => '2026-08-18 03:00:00',
        ]);
        $conversation->setRelation('patient', $patient);
        $clinic = new OnlineRegistrationAudit([
            'clinic_code' => 'INT',
            'clinic_name' => 'Poliklinik Penyakit Dalam',
        ]);
        $clinic->setAttribute('total', 1);

        $dashboardData = [
            'summary' => [
                'patient_users' => 1,
                'registrations_today' => 1,
                'active_users_today' => 1,
                'waiting_tickets' => 1,
            ],
            'system' => [
                'active_accounts' => 2,
                'new_accounts_today' => 1,
                'pwa_installations' => 1,
                'active_promotions' => 1,
                'bpjs_errors_today' => 0,
            ],
            'service' => [
                'waiting_admin' => 1,
                'waiting_patient' => 0,
                'closed_today' => 0,
            ],
            'trend' => collect(range(12, 18))->map(fn (int $day): array => [
                'date' => "2026-08-{$day}",
                'label' => 'Sen',
                'date_label' => "{$day} Agu",
                'registrations' => $day === 18 ? 1 : 0,
                'active_users' => $day === 18 ? 1 : 0,
            ]),
            'trendTotals' => ['registrations' => 1, 'active_users' => 1],
            'registrationPeak' => 1,
            'activeUserPeak' => 1,
            'topClinics' => collect([$clinic]),
            'recentRegistrations' => collect([$registration]),
            'recentConversations' => collect([$conversation]),
            'recentUsers' => collect([$patient, $admin]),
        ];

        $this->mock(AdminDashboardService::class, function (MockInterface $mock) use ($dashboardData): void {
            $mock->shouldReceive('overview')
                ->once()
                ->withArgs(fn (CarbonInterface $at): bool => $at->timezoneName === 'Asia/Jakarta')
                ->andReturn($dashboardData);
        });

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('e-pasien.admin-dashboard')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['patient_users'] === 1
                && $summary['registrations_today'] === 1
                && $summary['active_users_today'] === 1
                && $summary['waiting_tickets'] === 1)
            ->assertSeeText('Selamat datang, Admin')
            ->assertSeeText('Pusat kendali E-Pasien')
            ->assertSeeText('Aktivitas layanan digital')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('Informasi jadwal kontrol')
            ->assertSee('admin-dashboard.css');
    }

    private function administrator(): User
    {
        $admin = $this->user([
            'id' => 1,
            'name' => 'Admin Rumah Sakit',
            'email' => 'admin@example.test',
        ]);
        $admin->setRelation('roles', collect([new Role([
            'name' => 'Administrator',
            'guard_name' => 'web',
        ])]));

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function user(array $attributes): User
    {
        $user = new User(array_merge([
            'name' => 'Pengguna E-Pasien',
            'username' => null,
            'email' => 'user@example.test',
            'status' => true,
        ], $attributes));
        $user->id = $attributes['id'] ?? 1;
        $user->created_at = now()->subHour();
        $user->setRelation('roles', collect());
        $user->setRelation('permissions', collect());

        return $user;
    }
}
