<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\menu\RiwayatPemeriksaanRepository;
use App\Services\epasien\menu\RiwayatPemeriksaanService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RiwayatPemeriksaanServiceTest extends TestCase
{
    public function test_completed_history_formats_repository_data_for_cards(): void
    {
        $repository = $this->createMock(RiwayatPemeriksaanRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateCompletedExaminations')
            ->with(
                '000123',
                'Ranap',
                '2026-07-01',
                '2026-07-31',
                'D001',
                8
            )
            ->willReturn(new LengthAwarePaginator([(object) [
                'no_reg' => '002',
                'no_rawat' => '2026/07/21/000002',
                'tgl_registrasi' => '2026-07-21',
                'jam_reg' => '09:30:00',
                'status_lanjut' => 'Ranap',
                'status_bayar' => 'Sudah Bayar',
                'stts_daftar' => 'Lama',
                'kd_dokter' => 'D001',
                'kd_poli' => 'IGDK',
                'kd_pj' => 'UMU',
                'nm_dokter' => 'dr. Sehat',
                'nm_poli' => 'IGD',
                'png_jawab' => 'Umum',
            ]], 1, 8));

        $service = new RiwayatPemeriksaanService($repository);
        $history = $service->completedHistory(
            new User(['username' => ' 000123 ']),
            'Ranap',
            '2026-07-01',
            '2026-07-31',
            'D001'
        );
        $item = $history->items()[0];

        $this->assertSame('Rawat Inap', $item['jenis_layanan']);
        $this->assertSame('ranap', $item['layanan_tone']);
        $this->assertSame('Selasa, 21 Juli 2026', $item['tanggal_lengkap']);
        $this->assertSame('09:30', $item['jam']);
        $this->assertSame('dr. Sehat', $item['dokter']);
    }

    public function test_empty_username_does_not_query_examination_data(): void
    {
        $repository = $this->createMock(RiwayatPemeriksaanRepository::class);
        $repository->expects($this->never())->method('findPatient');
        $repository->expects($this->never())->method('paginateCompletedExaminations');
        $repository->expects($this->never())->method('completedExaminationCounts');
        $repository->expects($this->never())->method('completedExaminationDoctors');

        $service = new RiwayatPemeriksaanService($repository);
        $user = new User(['username' => '   ']);

        $this->assertNull($service->patientForUser($user));
        $this->assertSame(0, $service->completedHistory($user)->total());
        $this->assertSame([
            'all' => 0,
            'Ralan' => 0,
            'Ranap' => 0,
        ], $service->completedCounts($user));
        $this->assertSame([], $service->completedDoctors($user));
    }

    public function test_completed_doctors_are_formatted_for_the_filter(): void
    {
        $repository = $this->createMock(RiwayatPemeriksaanRepository::class);
        $repository
            ->expects($this->once())
            ->method('completedExaminationDoctors')
            ->with('000123')
            ->willReturn(new Collection([
                (object) ['kd_dokter' => 'D001', 'nm_dokter' => 'dr. Sehat'],
                (object) ['kd_dokter' => 'D002', 'nm_dokter' => null],
            ]));

        $doctors = (new RiwayatPemeriksaanService($repository))
            ->completedDoctors(new User(['username' => ' 000123 ']));

        $this->assertSame([
            ['code' => 'D001', 'name' => 'dr. Sehat'],
            ['code' => 'D002', 'name' => 'D002'],
        ], $doctors);
    }

    public function test_ralan_resume_is_formatted_into_medical_sections(): void
    {
        $repository = $this->createMock(RiwayatPemeriksaanRepository::class);
        $repository
            ->expects($this->once())
            ->method('findResumeForCompletedVisit')
            ->with('000123', '2026/07/20/000007', 'Ralan')
            ->willReturn((object) [
                'nm_dokter' => 'dr. Sehat',
                'keluhan_utama' => 'Demam dan batuk',
                'diagnosa_utama' => 'Infeksi saluran napas akut',
                'kd_diagnosa_utama' => 'J06.9',
                'prosedur_utama' => 'Pemeriksaan umum',
                'kd_prosedur_utama' => '89.7',
                'kondisi_pulang' => 'Membaik',
                'obat_pulang' => 'Parasetamol',
            ]);

        $service = new RiwayatPemeriksaanService($repository);
        $resume = $service->resumeForUser(
            new User(['username' => ' 000123 ']),
            '2026/07/20/000007',
            'Ralan'
        );

        $this->assertNotNull($resume);
        $this->assertSame('Rawat Jalan', $resume['jenis_layanan']);
        $this->assertSame('dr. Sehat', $resume['dokter']);
        $this->assertSame([
            'Ringkasan Klinis',
            'Diagnosis',
            'Prosedur',
            'Rencana Pulang',
        ], array_column($resume['sections'], 'title'));
        $this->assertSame('J06.9', $resume['sections'][1]['items'][0]['code']);
    }

    public function test_ranap_resume_includes_inpatient_only_fields(): void
    {
        $repository = $this->createMock(RiwayatPemeriksaanRepository::class);
        $repository
            ->expects($this->once())
            ->method('findResumeForCompletedVisit')
            ->with('000123', '2026/07/21/000008', 'Ranap')
            ->willReturn((object) [
                'nm_dokter' => 'dr. Rawat Inap',
                'diagnosa_awal' => 'Observasi demam',
                'pemeriksaan_fisik' => 'Keadaan umum baik',
                'tindakan_dan_operasi' => 'Terapi cairan',
                'cara_keluar' => 'Atas persetujuan dokter',
                'kontrol' => 'Kontrol tiga hari lagi',
            ]);

        $service = new RiwayatPemeriksaanService($repository);
        $resume = $service->resumeForUser(
            new User(['username' => '000123']),
            '2026/07/21/000008',
            'Ranap'
        );

        $this->assertNotNull($resume);
        $this->assertSame('Rawat Inap', $resume['jenis_layanan']);
        $this->assertSame([
            'Ringkasan Perawatan',
            'Penunjang dan Terapi',
            'Kondisi dan Tindak Lanjut',
        ], array_column($resume['sections'], 'title'));
        $this->assertSame(
            'Atas persetujuan dokter',
            $resume['sections'][2]['items'][0]['value']
        );
    }

    public function test_payment_formats_every_billing_column_and_calculates_the_total(): void
    {
        $repository = $this->createMock(RiwayatPemeriksaanRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBillingForCompletedVisit')
            ->with('000123', '2026/07/20/000007')
            ->willReturn([
                'visit' => (object) [
                    'no_rawat' => '2026/07/20/000007',
                    'no_rkm_medis' => '000123',
                    'tgl_registrasi' => '2026-07-20',
                    'jam_reg' => '08:15:00',
                    'status_lanjut' => 'Ralan',
                    'status_bayar' => 'Sudah Bayar',
                    'nm_pasien' => 'Budi Santoso',
                    'nm_dokter' => 'dr. Sehat',
                    'nm_poli' => 'Poli Umum',
                    'png_jawab' => 'Umum',
                ],
                'rows' => new Collection([
                    (object) [
                        'noindex' => 0,
                        'no_rawat' => '2026/07/20/000007',
                        'tgl_byr' => '2026-07-20',
                        'no' => 'No.Nota',
                        'nm_perawatan' => ': 2026/07/20/RJ0007',
                        'pemisah' => '',
                        'biaya' => 0,
                        'jumlah' => 0,
                        'tambahan' => 0,
                        'totalbiaya' => 0,
                        'status' => '-',
                    ],
                    (object) [
                        'noindex' => 1,
                        'no_rawat' => '2026/07/20/000007',
                        'tgl_byr' => '2026-07-20',
                        'no' => '',
                        'nm_perawatan' => 'Jasa Periksa',
                        'pemisah' => ':',
                        'biaya' => 25000,
                        'jumlah' => 1,
                        'tambahan' => 0,
                        'totalbiaya' => 25000,
                        'status' => 'Ralan Dokter',
                    ],
                    (object) [
                        'noindex' => 2,
                        'no_rawat' => '2026/07/20/000007',
                        'tgl_byr' => '2026-07-20',
                        'no' => '',
                        'nm_perawatan' => 'Diskon',
                        'pemisah' => ':',
                        'biaya' => 5000,
                        'jumlah' => 1,
                        'tambahan' => 0,
                        'totalbiaya' => -5000,
                        'status' => 'Potongan',
                    ],
                ]),
            ]);

        $payment = (new RiwayatPemeriksaanService($repository))->paymentForUser(
            new User(['username' => ' 000123 ']),
            ' 2026/07/20/000007 '
        );

        $this->assertNotNull($payment);
        $this->assertSame('2026/07/20/RJ0007', $payment['nomor_nota']);
        $this->assertSame('Senin, 20 Juli 2026', $payment['tanggal_bayar_lengkap']);
        $this->assertSame(20000.0, $payment['summary']['total']);
        $this->assertSame(5000.0, $payment['summary']['pengurang']);
        $this->assertCount(3, $payment['rows']);
        $this->assertSame([
            'noindex',
            'no_rawat',
            'tgl_byr',
            'no',
            'nm_perawatan',
            'pemisah',
            'biaya',
            'jumlah',
            'tambahan',
            'totalbiaya',
            'status',
            'type',
            'label',
            'description',
        ], array_keys($payment['rows'][0]));
    }
}
