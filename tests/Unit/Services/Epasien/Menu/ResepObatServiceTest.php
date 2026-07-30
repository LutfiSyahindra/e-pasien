<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\menu\PermintaanTindakan\ResepObatRepository;
use App\Services\epasien\menu\PermintaanTindakan\ResepObatService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ResepObatServiceTest extends TestCase
{
    public function test_prescriptions_are_grouped_and_formatted_by_source(): void
    {
        $repository = $this->createMock(ResepObatRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginatePrescriptions')
            ->with(
                '000123',
                null,
                'ralan',
                '2026-07-01',
                '2026-07-31',
                'obat',
                8
            )
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'nomor_resep' => 'RSP001',
                    'sumber' => 'dokter',
                    'no_rawat' => '2026/07/29/000001',
                    'tanggal' => '2026-07-29',
                    'jam' => '08:15:00',
                    'status_layanan' => 'ralan',
                    'status_proses' => 'selesai',
                    'tanggal_selesai' => '2026-07-29',
                    'jam_selesai' => '09:30:00',
                    'nm_dokter' => 'dr. Sehat',
                    'nm_poli' => 'Poli Umum',
                ],
                (object) [
                    'nomor_resep' => 'PRP001',
                    'sumber' => 'pulang',
                    'no_rawat' => '2026/07/29/000002',
                    'tanggal' => '2026-07-29',
                    'jam' => '12:00:00',
                    'status_layanan' => 'ranap',
                    'status_proses' => 'menunggu',
                    'tanggal_selesai' => '0000-00-00',
                    'jam_selesai' => '00:00:00',
                    'nm_dokter' => 'dr. Rawat',
                    'nm_poli' => null,
                ],
            ], 2, 8));
        $repository
            ->expects($this->once())
            ->method('medicineItems')
            ->with(['RSP001'])
            ->willReturn(new Collection([
                (object) [
                    'no_resep' => 'RSP001',
                    'kode_brng' => 'OBT001',
                    'jml' => 10,
                    'aturan_pakai' => '3 x 1 sesudah makan',
                    'nama_brng' => 'Amoxicillin 500 mg',
                    'kode_sat' => 'TAB',
                ],
            ]));
        $repository
            ->expects($this->once())
            ->method('compoundedItems')
            ->with(['RSP001'])
            ->willReturn(new Collection([
                (object) [
                    'no_resep' => 'RSP001',
                    'no_racik' => '1',
                    'nama_racik' => 'Puyer Batuk',
                    'kd_racik' => 'R01',
                    'jml_dr' => 10,
                    'aturan_pakai' => '3 x 1',
                    'keterangan' => 'Sesudah makan',
                    'nm_racik' => 'Puyer',
                ],
            ]));
        $repository
            ->expects($this->once())
            ->method('dischargeItems')
            ->with(['PRP001'])
            ->willReturn(new Collection([
                (object) [
                    'no_permintaan' => 'PRP001',
                    'kode_brng' => 'OBT003',
                    'jml' => 6,
                    'dosis' => '2 x 1',
                    'nama_brng' => 'Vitamin',
                    'kode_sat' => 'TAB',
                ],
            ]));

        $prescriptions = (new ResepObatService($repository))
            ->prescriptionsForUser(
                new User(['username' => ' 000123 ']),
                null,
                'ralan',
                '2026-07-01',
                '2026-07-31',
                ' obat '
            );
        $doctorPrescription = $prescriptions->items()[0];
        $dischargePrescription = $prescriptions->items()[1];

        $this->assertSame(
            'Sudah Diserahkan',
            $doctorPrescription['status_label']
        );
        $this->assertSame(
            'Rabu, 29 Juli 2026',
            $doctorPrescription['tanggal_lengkap']
        );
        $this->assertSame(
            'Amoxicillin 500 mg',
            $doctorPrescription['obat'][0]['nama']
        );
        $this->assertSame('Puyer', $doctorPrescription['racikan'][0]['metode']);
        $this->assertSame(2, $doctorPrescription['jumlah_item']);
        $this->assertSame('Resep Pulang', $dischargePrescription['jenis_resep']);
        $this->assertSame(
            'Menunggu Validasi',
            $dischargePrescription['status_label']
        );
        $this->assertSame('Rawat Inap', $dischargePrescription['poli']);
        $this->assertSame(
            '2 x 1',
            $dischargePrescription['obat'][0]['aturan_pakai']
        );
    }

    public function test_empty_username_never_queries_prescription_data(): void
    {
        $repository = $this->createMock(ResepObatRepository::class);
        $repository->expects($this->never())->method('findPatient');
        $repository->expects($this->never())->method('paginatePrescriptions');
        $repository->expects($this->never())->method('prescriptionCounts');
        $repository->expects($this->never())->method('medicineItems');
        $repository->expects($this->never())->method('compoundedItems');
        $repository->expects($this->never())->method('dischargeItems');

        $service = new ResepObatService($repository);
        $user = new User(['username' => '   ']);

        $this->assertNull($service->patientForUser($user));
        $this->assertSame(0, $service->prescriptionsForUser($user)->total());
        $this->assertSame([
            'all' => 0,
            'ralan' => 0,
            'ranap' => 0,
            'dokter' => 0,
            'pulang' => 0,
        ], $service->countsForUser($user));
    }
}
