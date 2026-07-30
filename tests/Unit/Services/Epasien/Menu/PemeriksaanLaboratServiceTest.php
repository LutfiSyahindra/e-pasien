<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\menu\PermintaanTindakan\PemeriksaanLaboratRepository;
use App\Services\epasien\menu\PermintaanTindakan\PemeriksaanLaboratService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PemeriksaanLaboratServiceTest extends TestCase
{
    public function test_requests_are_formatted_with_progress_and_requested_tests(): void
    {
        $repository = $this->createMock(PemeriksaanLaboratRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateRequests')
            ->with(
                '000123',
                'selesai',
                'Ralan',
                '2026-07-01',
                '2026-07-31',
                'darah',
                8
            )
            ->willReturn(new LengthAwarePaginator([(object) [
                'noorder' => 'PL20260729001',
                'no_rawat' => '2026/07/29/000001',
                'tgl_permintaan' => '2026-07-29',
                'jam_permintaan' => '08:15:00',
                'tgl_sampel' => '2026-07-29',
                'jam_sampel' => '08:45:00',
                'tgl_hasil' => '2026-07-29',
                'jam_hasil' => '10:00:00',
                'status' => 'Ralan',
                'nm_dokter' => 'dr. Sehat',
                'nm_poli' => 'Poli Umum',
                'diagnosa_klinis' => 'Demam',
                'informasi_tambahan' => 'Puasa 8 jam',
                'jumlah_pemeriksaan' => 1,
                'jumlah_hasil' => 3,
            ]], 1, 8));
        $repository
            ->expects($this->once())
            ->method('requestedExaminations')
            ->with(['PL20260729001'])
            ->willReturn(new Collection([
                (object) [
                    'noorder' => 'PL20260729001',
                    'kd_jenis_prw' => 'LAB001',
                    'nm_perawatan' => 'Darah Lengkap',
                ],
            ]));

        $requests = (new PemeriksaanLaboratService($repository))->requestsForUser(
            new User(['username' => ' 000123 ']),
            'selesai',
            'Ralan',
            '2026-07-01',
            '2026-07-31',
            ' darah '
        );
        $request = $requests->items()[0];

        $this->assertSame('Hasil Tersedia', $request['status_label']);
        $this->assertSame('Rabu, 29 Juli 2026', $request['tanggal_hasil_lengkap']);
        $this->assertSame('10:00', $request['jam_hasil']);
        $this->assertTrue($request['hasil_tersedia']);
        $this->assertSame(
            'Darah Lengkap',
            $request['pemeriksaan_diminta'][0]['nama']
        );
    }

    public function test_result_is_scoped_to_the_user_and_grouped_by_examination(): void
    {
        $repository = $this->createMock(PemeriksaanLaboratRepository::class);
        $repository
            ->expects($this->once())
            ->method('findRequestForPatient')
            ->with('000123', 'PL20260729001')
            ->willReturn((object) [
                'noorder' => 'PL20260729001',
                'no_rawat' => '2026/07/29/000001',
                'tgl_permintaan' => '2026-07-29',
                'jam_permintaan' => '08:15:00',
                'tgl_sampel' => '2026-07-29',
                'jam_sampel' => '08:45:00',
                'tgl_hasil' => '2026-07-29',
                'jam_hasil' => '10:00:00',
                'status' => 'Ralan',
                'nm_dokter' => 'dr. Sehat',
                'nm_poli' => 'Poli Umum',
                'diagnosa_klinis' => 'Demam',
                'informasi_tambahan' => '',
                'jumlah_pemeriksaan' => 1,
                'jumlah_hasil' => 2,
            ]);
        $repository
            ->expects($this->once())
            ->method('requestedExaminations')
            ->with(['PL20260729001'])
            ->willReturn(new Collection([
                (object) [
                    'noorder' => 'PL20260729001',
                    'kd_jenis_prw' => 'LAB001',
                    'nm_perawatan' => 'Darah Lengkap',
                ],
            ]));
        $repository
            ->expects($this->once())
            ->method('resultRows')
            ->with(
                'PL20260729001',
                '2026/07/29/000001',
                '2026-07-29'
            )
            ->willReturn(new Collection([
                (object) [
                    'kd_jenis_prw' => 'LAB001',
                    'id_template' => '1',
                    'nm_perawatan' => 'Darah Lengkap',
                    'nama_parameter' => 'Hemoglobin',
                    'nilai' => '13.5',
                    'satuan' => 'g/dL',
                    'nilai_rujukan' => '12-16',
                    'keterangan' => 'Normal',
                    'jam' => '09:59:48',
                ],
                (object) [
                    'kd_jenis_prw' => 'LAB001',
                    'id_template' => '2',
                    'nm_perawatan' => 'Darah Lengkap',
                    'nama_parameter' => 'Leukosit',
                    'nilai' => '12.000',
                    'satuan' => '/uL',
                    'nilai_rujukan' => '4.000-10.000',
                    'keterangan' => 'Tinggi',
                    'jam' => '09:59:48',
                ],
            ]));

        $result = (new PemeriksaanLaboratService($repository))->resultForUser(
            new User(['username' => ' 000123 ']),
            ' PL20260729001 '
        );

        $this->assertNotNull($result);
        $this->assertSame(1, $result['ringkasan']['jumlah_jenis']);
        $this->assertSame(2, $result['ringkasan']['jumlah_parameter']);
        $this->assertSame(1, $result['ringkasan']['jumlah_catatan']);
        $this->assertSame('09:59', $result['permintaan']['jam_hasil_aktual']);
        $this->assertSame('Darah Lengkap', $result['kelompok_hasil'][0]['nama']);
        $this->assertFalse(
            $result['kelompok_hasil'][0]['parameter'][0]['memiliki_catatan']
        );
        $this->assertTrue(
            $result['kelompok_hasil'][0]['parameter'][1]['memiliki_catatan']
        );
    }

    public function test_empty_username_never_queries_patient_laboratory_data(): void
    {
        $repository = $this->createMock(PemeriksaanLaboratRepository::class);
        $repository->expects($this->never())->method('findPatient');
        $repository->expects($this->never())->method('paginateRequests');
        $repository->expects($this->never())->method('requestCounts');
        $repository->expects($this->never())->method('findRequestForPatient');

        $service = new PemeriksaanLaboratService($repository);
        $user = new User(['username' => '   ']);

        $this->assertNull($service->patientForUser($user));
        $this->assertSame(0, $service->requestsForUser($user)->total());
        $this->assertSame([
            'all' => 0,
            'menunggu' => 0,
            'proses' => 0,
            'selesai' => 0,
        ], $service->countsForUser($user));
        $this->assertNull($service->resultForUser($user, 'PL001'));
    }
}
