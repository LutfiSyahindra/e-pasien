<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\menu\DaftarOnlineRepository;
use App\Services\epasien\menu\DaftarOnlineService;
use PHPUnit\Framework\TestCase;

class DaftarOnlineServiceTest extends TestCase
{
    public function test_patient_for_user_uses_repository_with_trimmed_medical_record_number(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('findPatient')
            ->with('000123')
            ->willReturn($patient);

        $service = new DaftarOnlineService($repository);
        $user = new User(['username' => ' 000123 ']);

        $this->assertSame($patient, $service->patientForUser($user));
    }

    public function test_patient_for_user_skips_repository_when_username_is_empty(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->never())
            ->method('findPatient');

        $service = new DaftarOnlineService($repository);
        $user = new User(['username' => '   ']);

        $this->assertNull($service->patientForUser($user));
    }

    public function test_available_schedules_gets_schedule_metrics_from_repository(): void
    {
        $schedule = (object) [
            'kd_dokter' => ' D001 ',
            'nm_dokter' => ' dr. Budi ',
            'kd_poli' => ' POL01 ',
            'nm_poli' => ' Poli Umum ',
            'hari_kerja' => ' Senin ',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kuota' => 20,
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('getSchedules')
            ->with(['SENIN'])
            ->willReturn(collect([$schedule]));
        $repository
            ->expects($this->once())
            ->method('countActiveRegistrations')
            ->with('2026-07-27', 'D001', 'POL01')
            ->willReturn(4);
        $repository
            ->expects($this->once())
            ->method('previewNextRegistrationNumber')
            ->with('2026-07-27', 'D001', 'POL01')
            ->willReturn('005');

        $service = new DaftarOnlineService($repository);
        $result = $service->availableSchedules('2026-07-27');

        $this->assertSame('Senin', $result['hari']);
        $this->assertSame('D001', $result['schedules'][0]['kd_dokter']);
        $this->assertSame('POL01', $result['schedules'][0]['kd_poli']);
        $this->assertSame(4, $result['schedules'][0]['terdaftar']);
        $this->assertSame('005', $result['schedules'][0]['estimasi_no_reg']);
    }

    public function test_register_sends_prepared_registration_to_repository(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'tgl_lahir' => '1990-01-01',
            'alamat' => 'Jl. Sehat',
            'keluarga' => 'AYAH',
            'namakeluarga' => 'Santoso',
        ];
        $schedule = (object) [
            'nm_dokter' => 'dr. Budi',
            'nm_poli' => 'Poli Umum',
        ];
        $penjamin = (object) [
            'kd_pj' => 'UMU',
            'png_jawab' => 'Umum',
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->method('findPatient')->with('000123')->willReturn($patient);
        $repository->method('findPendingRegistration')->with('000123')->willReturn(null);
        $repository
            ->method('findSchedule')
            ->with('D001', 'POL01', ['SENIN'])
            ->willReturn($schedule);
        $repository->method('findEligiblePenjamin')->with('UMU')->willReturn($penjamin);
        $repository
            ->expects($this->once())
            ->method('createRegistration')
            ->with($this->callback(function (array $registration): bool {
                $this->assertSame('2026-07-27', $registration['tgl_registrasi']);
                $this->assertSame('D001', $registration['kd_dokter']);
                $this->assertSame('POL01', $registration['kd_poli']);
                $this->assertSame('UMU', $registration['kd_pj']);
                $this->assertSame('000123', $registration['no_rkm_medis']);
                $this->assertSame('Santoso', $registration['p_jawab']);
                $this->assertSame('Belum', $registration['stts']);
                $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $registration['jam_reg']);

                return true;
            }))
            ->willReturn([
                'no_reg' => '001',
                'no_rawat' => '2026/07/27/000001',
                'tgl_registrasi' => '2026-07-27',
                'jam_reg' => '08:00:00',
                'kd_dokter' => 'D001',
                'kd_poli' => 'POL01',
                'kd_pj' => 'UMU',
                'stts' => 'Belum',
                'status_bayar' => 'Belum Bayar',
                'umurdaftar' => 36,
                'sttsumur' => 'Th',
            ]);

        $service = new DaftarOnlineService($repository);
        $user = new User(['username' => '000123']);
        $result = $service->register($user, [
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => ' D001 ',
            'kd_poli' => ' POL01 ',
            'kd_pj' => ' UMU ',
        ]);

        $this->assertSame('001', $result['registration']['no_reg']);
        $this->assertSame('2026/07/27/000001', $result['registration']['no_rawat']);
        $this->assertSame('dr. Budi', $result['registration']['dokter']);
        $this->assertSame('Poli Umum', $result['registration']['poli']);
    }
}
