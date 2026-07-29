<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\menu\PemeriksaanRadiologiRepository;
use App\Services\epasien\menu\PemeriksaanRadiologiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PemeriksaanRadiologiServiceTest extends TestCase
{
    public function test_requests_are_formatted_with_examinations_and_progress(): void
    {
        $repository = $this->createMock(
            PemeriksaanRadiologiRepository::class
        );
        $repository
            ->expects($this->once())
            ->method('paginateRequests')
            ->with(
                '000123',
                'selesai',
                'ralan',
                '2026-07-01',
                '2026-07-31',
                'thorax',
                8
            )
            ->willReturn(new LengthAwarePaginator([(object) [
                'noorder' => 'PR20260729001',
                'no_rawat' => '2026/07/29/000001',
                'tgl_permintaan' => '2026-07-29',
                'jam_permintaan' => '08:15:00',
                'tgl_sampel' => '2026-07-29',
                'jam_sampel' => '08:45:00',
                'tgl_hasil' => '2026-07-29',
                'jam_hasil' => '10:00:00',
                'status' => 'ralan',
                'nm_dokter' => 'dr. Sehat',
                'nm_poli' => 'Poli Umum',
                'diagnosa_klinis' => 'Batuk',
                'informasi_tambahan' => 'Evaluasi paru',
                'jumlah_pemeriksaan' => 1,
                'jumlah_hasil' => 1,
                'jumlah_gambar' => 2,
            ]], 1, 8));
        $repository
            ->expects($this->once())
            ->method('requestedExaminations')
            ->with(['PR20260729001'])
            ->willReturn(new Collection([
                (object) [
                    'noorder' => 'PR20260729001',
                    'kd_jenis_prw' => 'RAD001',
                    'nm_perawatan' => 'Thorax PA Dewasa',
                    'stts_bayar' => 'Belum',
                ],
            ]));

        $requests = (new PemeriksaanRadiologiService($repository))
            ->requestsForUser(
                new User(['username' => ' 000123 ']),
                'selesai',
                'ralan',
                '2026-07-01',
                '2026-07-31',
                ' thorax '
            );
        $request = $requests->items()[0];

        $this->assertSame('Hasil Tersedia', $request['status_label']);
        $this->assertSame(
            'Rabu, 29 Juli 2026',
            $request['tanggal_hasil_lengkap']
        );
        $this->assertSame('Rawat Jalan', $request['jenis_layanan']);
        $this->assertSame(2, $request['jumlah_gambar']);
        $this->assertSame(
            'Thorax PA Dewasa',
            $request['pemeriksaan_diminta'][0]['nama']
        );
    }

    public function test_result_follows_order_examination_result_and_image_flow(): void
    {
        $repository = $this->createMock(
            PemeriksaanRadiologiRepository::class
        );
        $request = (object) [
            'noorder' => 'PR20260729001',
            'no_rawat' => '2026/07/29/000001',
            'tgl_permintaan' => '2026-07-29',
            'jam_permintaan' => '08:15:00',
            'tgl_sampel' => '2026-07-29',
            'jam_sampel' => '08:45:00',
            'tgl_hasil' => '2026-07-29',
            'jam_hasil' => '10:00:00',
            'status' => 'ralan',
            'nm_dokter' => 'dr. Sehat',
            'nm_poli' => 'Poli Umum',
            'diagnosa_klinis' => 'Batuk',
            'informasi_tambahan' => '',
            'jumlah_pemeriksaan' => 1,
            'jumlah_hasil' => 1,
            'jumlah_gambar' => 1,
        ];
        $repository
            ->expects($this->once())
            ->method('findRequestForPatient')
            ->with('000123', 'PR20260729001')
            ->willReturn($request);
        $repository
            ->expects($this->once())
            ->method('requestedExaminations')
            ->with(['PR20260729001'])
            ->willReturn(new Collection([
                (object) [
                    'noorder' => 'PR20260729001',
                    'kd_jenis_prw' => 'RAD001',
                    'nm_perawatan' => 'Thorax PA Dewasa',
                    'stts_bayar' => 'Belum',
                ],
            ]));
        $repository
            ->expects($this->once())
            ->method('resultRows')
            ->with('2026/07/29/000001')
            ->willReturn(new Collection([
                (object) [
                    'tgl_periksa' => '2026-07-29',
                    'jam' => '10:00:00',
                    'hasil' => 'Cor dan pulmo dalam batas normal.',
                ],
                (object) [
                    'tgl_periksa' => '2026-07-20',
                    'jam' => '09:00:00',
                    'hasil' => 'Hasil lama pada nomor rawat yang sama.',
                ],
            ]));
        $repository
            ->expects($this->once())
            ->method('imageRows')
            ->with('2026/07/29/000001')
            ->willReturn(new Collection([
                (object) [
                    'tgl_periksa' => '2026-07-29',
                    'jam' => '10:00:00',
                    'lokasi_gambar' => 'pages/upload/thorax-01.jpg',
                ],
            ]));

        $result = (new PemeriksaanRadiologiService($repository))
            ->resultForUser(
                new User(['username' => ' 000123 ']),
                ' PR20260729001 '
            );

        $this->assertNotNull($result);
        $this->assertSame(1, $result['ringkasan']['jumlah_pemeriksaan']);
        $this->assertSame(1, $result['ringkasan']['jumlah_hasil']);
        $this->assertSame(1, $result['ringkasan']['jumlah_gambar']);
        $this->assertSame(
            'Thorax PA Dewasa',
            $result['pemeriksaan'][0]['nama']
        );
        $this->assertSame(
            'Cor dan pulmo dalam batas normal.',
            $result['hasil'][0]['narasi']
        );
        $this->assertStringContainsString(
            '/pemeriksaan-radiologi/PR20260729001/gambar/0',
            $result['gambar'][0]['url']
        );
    }

    public function test_empty_username_never_queries_radiology_data(): void
    {
        $repository = $this->createMock(
            PemeriksaanRadiologiRepository::class
        );
        $repository->expects($this->never())->method('findPatient');
        $repository->expects($this->never())->method('paginateRequests');
        $repository->expects($this->never())->method('requestCounts');
        $repository->expects($this->never())->method('findRequestForPatient');

        $service = new PemeriksaanRadiologiService($repository);
        $user = new User(['username' => '   ']);

        $this->assertNull($service->patientForUser($user));
        $this->assertSame(0, $service->requestsForUser($user)->total());
        $this->assertSame([
            'all' => 0,
            'menunggu' => 0,
            'proses' => 0,
            'selesai' => 0,
        ], $service->countsForUser($user));
        $this->assertNull($service->resultForUser($user, 'PR001'));
    }
}
