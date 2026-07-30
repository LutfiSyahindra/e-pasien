<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\menu\PermintaanTindakan\OperasiRepository;
use App\Services\epasien\menu\PermintaanTindakan\OperasiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OperasiServiceTest extends TestCase
{
    public function test_operations_are_grouped_and_formatted_from_the_three_data_stages(): void
    {
        $repository = $this->createMock(OperasiRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateOperations')
            ->with(
                '000123',
                'selesai',
                'Ranap',
                '2026-07-01',
                '2026-07-31',
                'appendektomi',
                8
            )
            ->willReturn(new LengthAwarePaginator([
                $this->operationRow(),
            ], 1, 8));
        $repository
            ->expects($this->once())
            ->method('bookingPackages')
            ->with(['2026/07/29/000001'])
            ->willReturn(new Collection([
                (object) [
                    'no_rawat' => '2026/07/29/000001',
                    'tanggal_booking' => '2026-07-29',
                    'jam_mulai' => '08:00:00',
                    'kode_paket' => 'OP001',
                    'nm_perawatan' => 'Appendektomi',
                    'kategori' => 'Besar',
                ],
                (object) [
                    'no_rawat' => '2026/07/29/000001',
                    'tanggal_booking' => '2026-07-29',
                    'jam_mulai' => '08:00:00',
                    'kode_paket' => 'OP002',
                    'nm_perawatan' => 'Eksplorasi',
                    'kategori' => 'Sedang',
                ],
            ]));

        $operations = (new OperasiService($repository))->operationsForUser(
            new User(['username' => ' 000123 ']),
            'selesai',
            'Ranap',
            '2026-07-01',
            '2026-07-31',
            ' appendektomi '
        );
        $operation = $operations->items()[0];

        $this->assertSame('Laporan Tersedia', $operation['status_label']);
        $this->assertSame('Appendektomi', $operation['judul']);
        $this->assertSame(2, $operation['jumlah_tindakan']);
        $this->assertSame('08:00', $operation['jam_mulai']);
        $this->assertSame('2 jam', $operation['durasi_jadwal']);
        $this->assertTrue($operation['pelaksanaan_tercatat']);
        $this->assertTrue($operation['laporan_tersedia']);
        $this->assertSame('Rawat Inap', $operation['jenis_layanan']);
    }

    public function test_detail_is_owned_by_the_patient_and_contains_actual_and_report_data(): void
    {
        $repository = $this->createMock(OperasiRepository::class);
        $operation = $this->operationRow();
        $repository
            ->expects($this->once())
            ->method('findOperationForPatient')
            ->with(
                '000123',
                '2026/07/29/000001',
                '2026-07-29',
                '08:00:00'
            )
            ->willReturn($operation);
        $repository
            ->expects($this->once())
            ->method('bookingPackages')
            ->with(['2026/07/29/000001'])
            ->willReturn(new Collection([
                (object) [
                    'no_rawat' => '2026/07/29/000001',
                    'tanggal_booking' => '2026-07-29',
                    'jam_mulai' => '08:00:00',
                    'kode_paket' => 'OP001',
                    'nm_perawatan' => 'Appendektomi',
                    'kategori' => 'Besar',
                ],
            ]));
        $repository
            ->expects($this->once())
            ->method('performedOperations')
            ->with('2026/07/29/000001')
            ->willReturn(new Collection([
                (object) [
                    'no_rawat' => '2026/07/29/000001',
                    'tgl_operasi' => '2026-07-29 08:10:00',
                    'jenis_anasthesi' => 'Umum',
                    'kategori' => 'Besar',
                    'kode_paket' => 'OP001',
                    'status_layanan' => 'Ranap',
                    'nm_operator_utama' => 'dr. Operator',
                    'nm_dokter_anestesi' => 'dr. Anestesi',
                    'nm_perawatan' => 'Appendektomi',
                ],
            ]));
        $repository
            ->expects($this->once())
            ->method('operationReports')
            ->with('2026/07/29/000001')
            ->willReturn(new Collection([
                (object) [
                    'no_rawat' => '2026/07/29/000001',
                    'tanggal' => '2026-07-29 08:15:00',
                    'diagnosa_preop' => 'Apendisitis',
                    'diagnosa_postop' => 'Apendisitis akut',
                    'jaringan_dieksekusi' => 'Apendiks',
                    'selesaioperasi' => '2026-07-29 10:00:00',
                    'permintaan_pa' => 'Ya',
                    'laporan_operasi' => 'Operasi berjalan baik.',
                ],
            ]));

        $detail = (new OperasiService($repository))->detailForUser(
            new User(['username' => ' 000123 ']),
            ' 2026/07/29/000001 ',
            '2026-07-29',
            '08:00:00'
        );

        $this->assertNotNull($detail);
        $this->assertTrue($detail['pelaksanaan']['tersedia']);
        $this->assertSame(
            'dr. Operator',
            $detail['pelaksanaan']['operator_utama']
        );
        $this->assertTrue($detail['laporan']['tersedia']);
        $this->assertSame(
            'Apendisitis akut',
            $detail['laporan']['diagnosa_postoperasi']
        );
        $this->assertSame('1 jam 45 menit', $detail['laporan']['durasi']);
        $this->assertSame(
            'Operasi berjalan baik.',
            $detail['laporan']['narasi']
        );
    }

    public function test_empty_username_never_queries_operation_data(): void
    {
        $repository = $this->createMock(OperasiRepository::class);
        $repository->expects($this->never())->method('findPatient');
        $repository->expects($this->never())->method('paginateOperations');
        $repository->expects($this->never())->method('operationCounts');
        $repository->expects($this->never())->method('findOperationForPatient');

        $service = new OperasiService($repository);
        $user = new User(['username' => '   ']);

        $this->assertNull($service->patientForUser($user));
        $this->assertSame(0, $service->operationsForUser($user)->total());
        $this->assertSame([
            'all' => 0,
            'terjadwal' => 0,
            'proses' => 0,
            'menunggu_laporan' => 0,
            'selesai' => 0,
        ], $service->countsForUser($user));
        $this->assertNull($service->detailForUser(
            $user,
            '2026/07/29/000001',
            '2026-07-29',
            '08:00:00'
        ));
    }

    private function operationRow(): object
    {
        return (object) [
            'no_rawat' => '2026/07/29/000001',
            'tanggal_booking' => '2026-07-29',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'status_booking' => 'Selesai',
            'kd_dokter' => 'D001',
            'kd_ruang_ok' => 'OK1',
            'nm_dokter_operator' => 'dr. Operator',
            'nm_ruang_ok' => 'Kamar Operasi 1',
            'status_layanan' => 'Ranap',
            'nm_poli' => 'Bedah',
            'jumlah_booking_rawat' => 1,
            'jumlah_booking_tanggal' => 1,
            'jumlah_pelaksanaan_rawat' => 1,
            'jumlah_laporan_rawat' => 1,
            'pelaksanaan_tanggal_tepat' => '2026-07-29 08:10:00',
            'pelaksanaan_tanggal_terdekat' => '2026-07-29 08:10:00',
            'laporan_tanggal_tepat' => '2026-07-29 08:15:00',
            'laporan_tanggal_terdekat' => '2026-07-29 08:15:00',
        ];
    }
}
