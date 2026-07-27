<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\menu\DaftarOnlineRepository;
use App\Services\epasien\menu\DaftarOnlineService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

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
        $repository->method('findEligiblePenjamin')->with('UMU', false)->willReturn($penjamin);
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

    public function test_bpjs_registration_sends_changed_card_number_with_registration(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'tgl_lahir' => '1990-01-01',
            'alamat' => 'Jl. Sehat',
            'keluarga' => 'AYAH',
            'namakeluarga' => 'Santoso',
            'no_peserta' => '0001112223334',
        ];
        $schedule = (object) [
            'nm_dokter' => 'dr. Budi',
            'nm_poli' => 'Poli Umum',
        ];
        $penjamin = (object) [
            'kd_pj' => 'BPJ',
            'png_jawab' => 'BPJS Kesehatan',
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->method('findPatient')->with('000123')->willReturn($patient);
        $repository->method('findPendingRegistration')->with('000123')->willReturn(null);
        $repository->method('findSchedule')->willReturn($schedule);
        $repository->method('findEligiblePenjamin')->with('BPJ', false)->willReturn($penjamin);
        $repository
            ->expects($this->once())
            ->method('createRegistration')
            ->with(
                $this->callback(fn (array $registration): bool => $registration['kd_pj'] === 'BPJ'),
                '0009998887776'
            )
            ->willReturn([
                'no_reg' => '001',
                'no_rawat' => '2026/07/27/000001',
                'tgl_registrasi' => '2026-07-27',
                'jam_reg' => '08:00:00',
                'kd_dokter' => 'D001',
                'kd_poli' => 'POL01',
                'kd_pj' => 'BPJ',
                'stts' => 'Belum',
                'status_bayar' => 'Belum Bayar',
                'umurdaftar' => 36,
                'sttsumur' => 'Th',
            ]);

        $service = new DaftarOnlineService($repository);
        $result = $service->register(new User(['username' => '000123']), [
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
            'no_peserta' => ' 0009998887776 ',
        ]);

        $this->assertSame('0009998887776', $result['registration']['no_peserta']);
    }

    public function test_bpjs_registration_requires_card_number(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'tgl_lahir' => '1990-01-01',
            'alamat' => 'Jl. Sehat',
            'keluarga' => 'AYAH',
            'namakeluarga' => 'Santoso',
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->method('findPatient')->willReturn($patient);
        $repository->method('findPendingRegistration')->willReturn(null);
        $repository->method('findSchedule')->willReturn((object) [
            'nm_dokter' => 'dr. Budi',
            'nm_poli' => 'Poli Umum',
        ]);
        $repository->method('findEligiblePenjamin')->willReturn((object) [
            'kd_pj' => 'BPJ',
            'png_jawab' => 'BPJS Kesehatan',
        ]);
        $repository->expects($this->never())->method('createRegistration');

        $service = new DaftarOnlineService($repository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No. kartu wajib diisi');

        $service->register(new User(['username' => '000123']), [
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
        ]);
    }

    public function test_configured_registration_role_must_choose_patient_medical_record_number(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->expects($this->never())->method('findPatient');

        $service = new DaftarOnlineService($repository);
        $user = new User(['username' => 'PETUGAS01']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Nomor rekam medis pasien wajib dipilih');

        $service->register($user, [
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
        ], true);
    }

    public function test_regular_user_cannot_register_another_patient(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->expects($this->never())->method('findPatient');

        $service = new DaftarOnlineService($repository);
        $user = new User(['username' => '000123']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tidak memiliki akses untuk mendaftarkan pasien lain');

        $service->register($user, [
            'no_rkm_medis' => '000999',
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'UMU',
        ]);
    }
}
