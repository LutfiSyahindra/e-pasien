<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\bridging\AntrolRepository;
use App\Repositories\epasien\menu\DaftarOnlineRepository;
use App\Services\epasien\menu\DaftarOnlineService;
use Illuminate\Support\Carbon;
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

    public function test_search_patients_uses_trimmed_query_and_bounded_limit(): void
    {
        $patients = collect([
            (object) [
                'no_rkm_medis' => '000123',
                'nm_pasien' => 'Budi',
            ],
        ]);
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('searchPatients')
            ->with('Budi', '1990-05-17', 25)
            ->willReturn($patients);

        $service = new DaftarOnlineService($repository);

        $this->assertSame(
            $patients,
            $service->searchPatients(' Budi ', ' 1990-05-17 ', 100)
        );
    }

    public function test_search_patients_can_use_medical_record_without_birth_date(): void
    {
        $patients = collect([
            (object) [
                'no_rkm_medis' => '000123',
                'nm_pasien' => 'Budi',
            ],
        ]);
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('searchPatients')
            ->with('000123', null, 10)
            ->willReturn($patients);

        $service = new DaftarOnlineService($repository);

        $this->assertSame($patients, $service->searchPatients('000123'));
    }

    public function test_search_patients_skips_repository_when_query_is_empty(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->never())
            ->method('searchPatients');

        $service = new DaftarOnlineService($repository);

        $this->assertTrue($service->searchPatients('   ')->isEmpty());
    }

    public function test_pending_registration_cannot_be_cancelled_after_checkin(): void
    {
        $registration = (object) [
            'no_reg' => '001',
            'no_rawat' => '2026/07/28/000001',
            'no_rkm_medis' => '000123',
            'tgl_registrasi' => '2026-07-28',
            'jam_reg' => '08:00:00',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'UMU',
            'stts' => 'Belum',
            'status_bayar' => 'Belum Bayar',
            'status_lanjut' => 'Ralan',
            'stts_daftar' => 'Lama',
            'status_poli' => 'Lama',
            'biaya_reg' => 0,
            'umurdaftar' => 36,
            'sttsumur' => 'Th',
            'p_jawab' => 'Budi',
            'almt_pj' => 'Jl. Sehat',
            'hubunganpj' => 'DIRI SENDIRI',
            'nm_pasien' => 'Budi',
            'no_tlp' => '08123456789',
            'nm_dokter' => 'dr. Sehat',
            'nm_poli' => 'Poli Umum',
            'png_jawab' => 'Umum',
            'sudah_checkin' => 1,
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('findPendingRegistration')
            ->with('000123')
            ->willReturn($registration);

        $service = new DaftarOnlineService($repository);
        $result = $service->pendingRegistrationForMedicalRecord('000123');

        $this->assertTrue($result['sudah_checkin']);
        $this->assertFalse($result['can_cancel']);
    }

    public function test_cancel_registration_updates_own_pending_registration(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('findPendingMobileJknReference')
            ->with('2026/07/28/000001', '000123')
            ->willReturn(null);
        $repository
            ->expects($this->once())
            ->method('cancelPendingRegistration')
            ->with('2026/07/28/000001', '000123')
            ->willReturn(DaftarOnlineRepository::CANCELLATION_CANCELLED);

        $service = new DaftarOnlineService($repository);
        $result = $service->cancelRegistration(
            new User(['username' => ' 000123 ']),
            ' 2026/07/28/000001 ',
            '000123'
        );

        $this->assertSame('2026/07/28/000001', $result['no_rawat']);
        $this->assertSame('Batal', $result['status']);
    }

    public function test_cancel_registration_is_rejected_when_patient_has_checked_in(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('findPendingMobileJknReference')
            ->with('2026/07/28/000001', '000123')
            ->willReturn((object) [
                'nobooking' => '20260728000001',
                'statuskirim' => 'Sudah',
                'sudah_checkin' => 1,
            ]);
        $repository
            ->expects($this->once())
            ->method('cancelPendingRegistration')
            ->with('2026/07/28/000001', '000123')
            ->willReturn(DaftarOnlineRepository::CANCELLATION_CHECKED_IN);
        $antrolRepository = $this->createMock(AntrolRepository::class);
        $antrolRepository->expects($this->never())->method('cancelQueue');

        $service = new DaftarOnlineService($repository, $antrolRepository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('pasien sudah check-in di poli');

        $service->cancelRegistration(
            new User(['username' => '000123']),
            '2026/07/28/000001',
            '000123'
        );
    }

    public function test_cancel_jkn_registration_cancels_antrol_before_local_registration(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('findPendingMobileJknReference')
            ->with('2026/07/28/000001', '000123')
            ->willReturn((object) [
                'nobooking' => '20260728000001',
                'statuskirim' => 'Sudah',
                'sudah_checkin' => 0,
            ]);
        $repository
            ->expects($this->once())
            ->method('cancelPendingRegistration')
            ->with('2026/07/28/000001', '000123')
            ->willReturn(DaftarOnlineRepository::CANCELLATION_CANCELLED);
        $antrolRepository = $this->createMock(AntrolRepository::class);
        $antrolRepository
            ->expects($this->once())
            ->method('cancelQueue')
            ->with('20260728000001', 'Jadwal pasien berubah.')
            ->willReturn([
                'metadata' => [
                    'code' => 200,
                    'message' => 'Ok',
                ],
            ]);

        $service = new DaftarOnlineService($repository, $antrolRepository);
        $result = $service->cancelRegistration(
            new User(['username' => '000123']),
            '2026/07/28/000001',
            '000123',
            false,
            'Jadwal pasien berubah.'
        );

        $this->assertSame('Batal', $result['status']);
        $this->assertTrue($result['antrol']['cancelled']);
        $this->assertSame('20260728000001', $result['antrol']['booking_code']);
    }

    public function test_cancel_jkn_registration_keeps_local_registration_when_antrol_fails(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('findPendingMobileJknReference')
            ->with('2026/07/28/000001', '000123')
            ->willReturn((object) [
                'nobooking' => '20260728000001',
                'statuskirim' => 'Sudah',
                'sudah_checkin' => 0,
            ]);
        $repository->expects($this->never())->method('cancelPendingRegistration');
        $antrolRepository = $this->createMock(AntrolRepository::class);
        $antrolRepository
            ->expects($this->once())
            ->method('cancelQueue')
            ->willReturn([
                'metadata' => [
                    'code' => 400,
                    'message' => 'Kode booking tidak ditemukan.',
                ],
            ]);

        $service = new DaftarOnlineService($repository, $antrolRepository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            'Antrean JKN belum berhasil dibatalkan di BPJS: Kode booking tidak ditemukan.'
        );

        $service->cancelRegistration(
            new User(['username' => '000123']),
            '2026/07/28/000001',
            '000123'
        );
    }

    public function test_regular_user_cannot_cancel_another_patients_registration(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->expects($this->never())->method('findPendingMobileJknReference');
        $repository->expects($this->never())->method('cancelPendingRegistration');

        $service = new DaftarOnlineService($repository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tidak memiliki akses untuk membatalkan pendaftaran pasien lain');

        $service->cancelRegistration(
            new User(['username' => '000123']),
            '2026/07/28/000001',
            '000999'
        );
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
            'kd_dokter_bpjs' => '12345',
            'nm_dokter_bpjs' => 'Dr. Budi BPJS',
            'kd_poli_bpjs' => 'UMU',
            'nm_poli_bpjs' => 'Poli Umum BPJS',
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
        $this->assertSame('12345', $result['schedules'][0]['kd_dokter_bpjs']);
        $this->assertSame('UMU', $result['schedules'][0]['kd_poli_bpjs']);
        $this->assertSame('Poli Umum BPJS', $result['schedules'][0]['nm_poli_bpjs']);
        $this->assertSame(4, $result['schedules'][0]['terdaftar']);
        $this->assertSame('005', $result['schedules'][0]['estimasi_no_reg']);
    }

    public function test_available_schedules_excludes_doctors_with_zero_quota(): void
    {
        $schedule = (object) [
            'kd_dokter' => 'D001',
            'nm_dokter' => 'dr. Budi',
            'kd_poli' => 'POL01',
            'nm_poli' => 'Poli Umum',
            'hari_kerja' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kuota' => 0,
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->once())
            ->method('getSchedules')
            ->with(['SENIN'])
            ->willReturn(collect([$schedule]));
        $repository
            ->expects($this->never())
            ->method('countActiveRegistrations');
        $repository
            ->expects($this->never())
            ->method('previewNextRegistrationNumber');

        $service = new DaftarOnlineService($repository);
        $result = $service->availableSchedules('2026-07-27');

        $this->assertSame([], $result['schedules']);
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

    public function test_antrol_preview_builds_payload_without_creating_registration(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_ktp' => '3212345678987654',
            'no_tlp' => '081234567890',
            'tgl_daftar' => '2020-01-01',
        ];
        $schedule = (object) [
            'kd_dokter' => 'D001',
            'nm_dokter' => 'dr. Budi',
            'kd_poli' => 'POL01',
            'nm_poli' => 'Poli Anak',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kuota' => 30,
            'kd_dokter_bpjs' => '12345',
            'nm_dokter_bpjs' => 'Dr. Hendra',
            'kd_poli_bpjs' => 'ANA',
            'nm_poli_bpjs' => 'Anak',
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->expects($this->once())->method('findPatient')->with('000123')->willReturn($patient);
        $repository
            ->expects($this->once())
            ->method('findSchedule')
            ->with('D001', 'POL01', ['SENIN'])
            ->willReturn($schedule);
        $repository
            ->expects($this->once())
            ->method('previewNextRegistrationNumber')
            ->with('2026-07-27', 'D001', 'POL01')
            ->willReturn('005');
        $repository
            ->expects($this->once())
            ->method('countActiveRegistrations')
            ->with('2026-07-27', 'D001', 'POL01')
            ->willReturn(4);
        $repository
            ->expects($this->once())
            ->method('previewNextTreatmentNumber')
            ->with('2026-07-27')
            ->willReturn('2026/07/27/000021');
        $repository->expects($this->never())->method('createRegistration');

        $service = new DaftarOnlineService($repository);
        $result = $service->previewAntrolPayload(
            new User(['username' => 'PETUGAS01']),
            [
                'no_rkm_medis' => '000123',
                'tgl_registrasi' => '2026-07-27',
                'kd_dokter' => 'D001',
                'kd_poli' => 'POL01',
                'kd_pj' => 'BPJ',
                'no_peserta' => '0001234567890',
                'bpjs_document_type' => 'surat_kontrol',
                'bpjs_document_source' => 'surat_kontrol',
                'bpjs_document_number' => '0301R0110726K000001',
                'bpjs_document_date' => '2026-07-27',
                'bpjs_document_card_number' => '0001234567890',
                'bpjs_document_nik' => '3273012345678901',
                'bpjs_document_phone' => '085712345678',
                'bpjs_document_medical_record' => '000123',
                'bpjs_document_clinic_code' => 'ANA',
                'bpjs_document_clinic_name' => 'Anak - Respons BPJS',
                'bpjs_document_doctor_code' => '12345',
                'bpjs_document_doctor_name' => 'Dr. Kontrol BPJS',
            ],
            true
        );

        $this->assertTrue($result['preview_only']);
        $this->assertSame('POST', $result['method']);
        $this->assertSame('antrean/add', $result['endpoint']);
        $this->assertSame([
            'kodebooking' => '20260727000021',
            'jenispasien' => 'JKN',
            'nomorkartu' => '0001234567890',
            'nik' => '3273012345678901',
            'nohp' => '085712345678',
            'kodepoli' => 'ANA',
            'namapoli' => 'Anak - Respons BPJS',
            'pasienbaru' => 0,
            'norm' => '000123',
            'tanggalperiksa' => '2026-07-27',
            'kodedokter' => 12345,
            'namadokter' => 'Dr. Kontrol BPJS',
            'jampraktek' => '08:00-10:00',
            'jeniskunjungan' => 3,
            'nomorreferensi' => '0301R0110726K000001',
            'nomorantrean' => 'ANA-005',
            'angkaantrean' => 5,
            'estimasidilayani' => Carbon::create(
                2026,
                7,
                27,
                8,
                25,
                0,
                'Asia/Jakarta'
            )->getTimestampMs(),
            'sisakuotajkn' => 25,
            'kuotajkn' => 30,
            'sisakuotanonjkn' => 25,
            'kuotanonjkn' => 30,
            'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.',
        ], $result['payload']);
        $this->assertSame('Dokumen BPJS', $result['field_sources']['nik']);
        $this->assertSame('Dokumen BPJS', $result['field_sources']['nohp']);
        $this->assertSame('Dokumen BPJS', $result['field_sources']['kodepoli']);
        $this->assertSame('Dokumen BPJS', $result['field_sources']['kodedokter']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['preview_hash']);
        $this->assertSame('005', $result['final_data']['registration']['number']);
        $this->assertSame(
            '2026/07/27/000021',
            $result['final_data']['registration']['treatment_number']
        );
    }

    public function test_confirmed_mjkn_registration_is_saved_and_sent_to_antrol(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_ktp' => '3212345678987654',
            'no_tlp' => '081234567890',
            'tgl_daftar' => '2020-01-01',
            'tgl_lahir' => '1990-01-01',
            'namakeluarga' => 'Budi',
            'alamat' => 'Jl. Sehat',
            'keluarga' => 'DIRI SENDIRI',
        ];
        $schedule = (object) [
            'kd_dokter' => 'D001',
            'nm_dokter' => 'dr. Budi',
            'kd_poli' => 'POL01',
            'nm_poli' => 'Poli Anak',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kuota' => 30,
            'kd_dokter_bpjs' => '12345',
            'nm_dokter_bpjs' => 'Dr. Hendra',
            'kd_poli_bpjs' => 'ANA',
            'nm_poli_bpjs' => 'Anak',
        ];
        $penjamin = (object) [
            'kd_pj' => 'BPJ',
            'png_jawab' => 'BPJS Kesehatan',
        ];
        $data = [
            'no_rkm_medis' => '000123',
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
            'no_peserta' => '0001234567890',
            'bpjs_document_type' => 'surat_kontrol',
            'bpjs_document_source' => 'surat_kontrol',
            'bpjs_document_number' => '0301R0110726K000001',
            'bpjs_document_date' => '2026-07-27',
            'bpjs_document_card_number' => '0001234567890',
        ];
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository
            ->expects($this->exactly(3))
            ->method('findPatient')
            ->with('000123')
            ->willReturn($patient);
        $repository
            ->expects($this->exactly(3))
            ->method('findSchedule')
            ->with('D001', 'POL01', ['SENIN'])
            ->willReturn($schedule);
        $repository
            ->expects($this->exactly(2))
            ->method('previewNextRegistrationNumber')
            ->willReturn('005');
        $repository
            ->expects($this->exactly(2))
            ->method('countActiveRegistrations')
            ->willReturn(4);
        $repository
            ->expects($this->exactly(2))
            ->method('previewNextTreatmentNumber')
            ->willReturn('2026/07/27/000021');
        $repository
            ->expects($this->once())
            ->method('findPendingRegistration')
            ->with('000123')
            ->willReturn(null);
        $repository
            ->expects($this->once())
            ->method('findEligiblePenjamin')
            ->with('BPJ', true)
            ->willReturn($penjamin);
        $repository
            ->expects($this->once())
            ->method('createMjknRegistration')
            ->with(
                $this->callback(fn (array $registration): bool => $registration['kd_pj'] === 'BPJ'),
                '0001234567890',
                $this->callback(function (array $reference): bool {
                    return $reference['nobooking'] === '20260727000021'
                        && $reference['jeniskunjungan'] === '3 (Kontrol)'
                        && $reference['nomorantrean'] === '005'
                        && $reference['angkaantrean'] === '005'
                        && $reference['validasi'] === '0000-00-00 00:00:00'
                        && $reference['statuskirim'] === 'Belum';
                }),
                '005',
                '2026/07/27/000021'
            )
            ->willReturn([
                'no_reg' => '005',
                'no_rawat' => '2026/07/27/000021',
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
        $repository
            ->expects($this->once())
            ->method('markMjknReferenceSent')
            ->with('20260727000021')
            ->willReturn(true);
        $antrol = $this->createMock(AntrolRepository::class);
        $antrol
            ->expects($this->once())
            ->method('addQueue')
            ->with($this->callback(fn (array $payload): bool => $payload['nomorantrean'] === 'ANA-005'))
            ->willReturn([
                'metadata' => [
                    'code' => 200,
                    'message' => 'Ok',
                ],
            ]);
        $service = new DaftarOnlineService($repository, $antrol);
        $user = new User(['username' => '000123']);
        $preview = $service->previewAntrolPayload($user, $data);

        $result = $service->registerMjkn(
            $user,
            $data,
            $preview['preview_hash']
        );

        $this->assertSame('005', $result['registration']['no_reg']);
        $this->assertTrue($result['antrol']['sent']);
        $this->assertSame('Sudah', $result['antrol']['delivery_status']);
    }

    public function test_antrol_preview_maps_bpjs_document_source_to_visit_type(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->method('findPatient')->willReturn((object) [
            'no_rkm_medis' => '000123',
            'no_ktp' => '3212345678987654',
            'no_tlp' => '081234567890',
            'tgl_daftar' => '2020-01-01',
        ]);
        $repository->method('findSchedule')->willReturn((object) [
            'nm_dokter' => 'dr. Budi',
            'nm_poli' => 'Poli Anak',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kuota' => 30,
            'kd_dokter_bpjs' => '12345',
            'nm_dokter_bpjs' => 'Dr. Hendra',
            'kd_poli_bpjs' => 'ANA',
            'nm_poli_bpjs' => 'Anak',
        ]);
        $repository->method('previewNextRegistrationNumber')->willReturn('001');
        $repository->method('countActiveRegistrations')->willReturn(0);
        $repository->method('previewNextTreatmentNumber')->willReturn('2026/07/27/000001');
        $repository->expects($this->never())->method('createRegistration');

        $service = new DaftarOnlineService($repository);
        $baseData = [
            'no_rkm_medis' => '000123',
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
            'no_peserta' => '0001234567890',
            'bpjs_document_number' => '0301R0110726K000001',
        ];
        $cases = [
            ['surat_kontrol', 'surat_kontrol', 3],
            ['rujukan', 'rujukan_pcare', 1],
            ['rujukan', 'rujukan_rumah_sakit', 4],
        ];

        foreach ($cases as [$documentType, $documentSource, $expectedVisitType]) {
            $result = $service->previewAntrolPayload(
                new User(['username' => 'PETUGAS01']),
                array_merge($baseData, [
                    'bpjs_document_type' => $documentType,
                    'bpjs_document_source' => $documentSource,
                ]),
                true
            );

            $this->assertSame($expectedVisitType, $result['payload']['jeniskunjungan']);
        }
    }

    public function test_antrol_preview_rejects_control_letter_for_a_different_visit_date(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->method('findPatient')->willReturn((object) [
            'no_rkm_medis' => '000123',
            'no_ktp' => '3212345678987654',
            'no_tlp' => '081234567890',
        ]);
        $repository->expects($this->never())->method('findSchedule');
        $repository->expects($this->never())->method('createRegistration');

        $service = new DaftarOnlineService($repository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            'Tanggal kunjungan harus sama dengan tanggal rencana pada surat kontrol yang dipilih.'
        );

        $service->previewAntrolPayload(
            new User(['username' => 'PETUGAS01']),
            [
                'no_rkm_medis' => '000123',
                'tgl_registrasi' => '2026-07-27',
                'kd_dokter' => 'D001',
                'kd_poli' => 'POL01',
                'kd_pj' => 'BPJ',
                'no_peserta' => '0001234567890',
                'bpjs_document_type' => 'surat_kontrol',
                'bpjs_document_source' => 'surat_kontrol',
                'bpjs_document_number' => '0301R0110726K000001',
                'bpjs_document_date' => '2026-07-28',
            ],
            true
        );
    }

    public function test_antrol_preview_rejects_bpjs_registration_for_irm_clinic(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->expects($this->never())->method('findPatient');

        $service = new DaftarOnlineService($repository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Penjamin BPJ tidak dapat digunakan untuk pendaftaran online ke poli IRM');

        $service->previewAntrolPayload(
            new User(['username' => 'PETUGAS01']),
            [
                'tgl_registrasi' => '2026-07-27',
                'kd_dokter' => 'D001',
                'kd_poli' => 'IRM',
                'kd_pj' => 'BPJ',
            ],
            true
        );
    }

    public function test_patient_bpjs_registration_is_saved_directly_to_reg_periksa(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'tgl_lahir' => '1990-01-01',
            'alamat' => 'Jl. Sehat',
            'keluarga' => 'DIRI SENDIRI',
            'namakeluarga' => 'Budi',
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
        $repository
            ->method('findSchedule')
            ->with('D001', 'POL01', ['SENIN'])
            ->willReturn($schedule);
        $repository
            ->method('findEligiblePenjamin')
            ->with('BPJ', true)
            ->willReturn($penjamin);
        $repository
            ->expects($this->once())
            ->method('createRegistration')
            ->with(
                $this->callback(function (array $registration): bool {
                    $this->assertSame('BPJ', $registration['kd_pj']);
                    $this->assertSame('POL01', $registration['kd_poli']);
                    $this->assertSame('000123', $registration['no_rkm_medis']);

                    return true;
                }),
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

        $this->assertSame('BPJ', $result['registration']['kd_pj']);
        $this->assertSame('0009998887776', $result['registration']['no_peserta']);
    }

    public function test_patient_bpjs_registration_requires_card_number(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'tgl_lahir' => '1990-01-01',
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
        $repository
            ->method('findSchedule')
            ->with('D001', 'POL01', ['SENIN'])
            ->willReturn($schedule);
        $repository
            ->method('findEligiblePenjamin')
            ->with('BPJ', true)
            ->willReturn($penjamin);
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

    public function test_patient_bpjs_registration_is_rejected_for_irm_clinic(): void
    {
        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->expects($this->never())->method('findPatient');
        $repository->expects($this->never())->method('createRegistration');

        $service = new DaftarOnlineService($repository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Penjamin BPJ tidak dapat digunakan untuk pendaftaran online ke poli IRM');

        $service->register(new User(['username' => '000123']), [
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => 'D001',
            'kd_poli' => 'IRM',
            'kd_pj' => 'BPJ',
            'no_peserta' => '0009998887776',
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
