<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Models\User;
use App\Repositories\epasien\menu\RiwayatMcuRepository;
use App\Services\epasien\menu\RiwayatMcuService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class RiwayatMcuServiceTest extends TestCase
{
    public function test_assessments_are_scoped_and_formatted_for_the_user(): void
    {
        $repository = $this->createMock(RiwayatMcuRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateAssessments')
            ->with(
                '000123',
                '2026-07-01',
                '2026-07-31',
                'D001',
                'sehat',
                8
            )
            ->willReturn(new LengthAwarePaginator([
                $this->assessmentRow(),
            ], 1, 8));

        $assessments = (new RiwayatMcuService($repository))
            ->assessmentsForUser(
                new User(['username' => ' 000123 ']),
                '2026-07-01',
                '2026-07-31',
                ' D001 ',
                ' sehat '
            );
        $assessment = $assessments->items()[0];

        $this->assertSame(
            'Jumat, 24 Juli 2026',
            $assessment['tanggal_lengkap']
        );
        $this->assertSame('09:35', $assessment['jam']);
        $this->assertSame(
            '120/80 mmHg',
            $assessment['vitals']['tekanan_darah']
        );
        $this->assertSame('Sehat untuk bekerja.', $assessment['kesimpulan']);
    }

    public function test_detail_contains_structured_mcu_sections_and_bmi(): void
    {
        $repository = $this->createMock(RiwayatMcuRepository::class);
        $repository
            ->expects($this->once())
            ->method('findAssessmentForPatient')
            ->with('000123', '2026/07/24/000001')
            ->willReturn($this->assessmentRow());

        $detail = (new RiwayatMcuService($repository))->detailForUser(
            new User(['username' => '000123']),
            ' 2026/07/24/000001 '
        );

        $this->assertNotNull($detail);
        $this->assertSame(
            'Sehat untuk bekerja.',
            $detail['hasil_akhir']['kesimpulan']
        );
        $this->assertSame('22,8', $detail['vitals'][6]['value']);
        $this->assertSame('anamnesis', $detail['sections'][0]['key']);
        $this->assertSame('penunjang', $detail['sections'][7]['key']);
        $this->assertSame(
            'Normal — Tidak ada keluhan',
            $detail['sections'][6]['items'][1]['value']
        );
    }

    public function test_empty_username_never_queries_mcu_data(): void
    {
        $repository = $this->createMock(RiwayatMcuRepository::class);
        $repository->expects($this->never())->method('findPatient');
        $repository->expects($this->never())->method('paginateAssessments');
        $repository->expects($this->never())->method('assessmentSummary');
        $repository->expects($this->never())->method('assessmentDoctors');
        $repository->expects($this->never())
            ->method('findAssessmentForPatient');

        $service = new RiwayatMcuService($repository);
        $user = new User(['username' => '   ']);

        $this->assertNull($service->patientForUser($user));
        $this->assertSame(0, $service->assessmentsForUser($user)->total());
        $this->assertSame(0, $service->summaryForUser($user)['all']);
        $this->assertTrue($service->doctorsForUser($user)->isEmpty());
        $this->assertNull(
            $service->detailForUser($user, '2026/07/24/000001')
        );
    }

    private function assessmentRow(): object
    {
        return (object) [
            'no_rawat' => '2026/07/24/000001',
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi Santoso',
            'tanggal' => '2026-07-24 09:35:06',
            'status_lanjut' => 'Ralan',
            'nm_dokter' => 'dr. Sehat',
            'nm_poli' => 'Medical Check Up',
            'informasi' => 'Autoanamnesis',
            'rps' => 'Tidak ada keluhan.',
            'rpk' => 'Tidak ada.',
            'rpd' => 'Tidak ada.',
            'alergi' => 'Tidak ada.',
            'keadaan' => 'Baik',
            'kesadaran' => 'Composmentis',
            'td' => '120/80',
            'nadi' => '80',
            'rr' => '18',
            'tb' => '170',
            'bb' => '66',
            'suhu' => '36.5',
            'ekstrimitas_atas' => 'Normal',
            'ekstrimitas_atas_ket' => 'Tidak ada keluhan',
            'ekstrimitas_bawah' => 'Normal',
            'ekstrimitas_bawah_ket' => '',
            'laborat' => 'Dalam batas normal.',
            'radiologi' => 'Dalam batas normal.',
            'ekg' => 'Sinus rhythm.',
            'spirometri' => 'Normal.',
            'audiometri' => 'Normal.',
            'treadmill' => 'Tidak dilakukan.',
            'lainlain' => '-',
            'merokok' => 'Tidak',
            'alkohol' => 'Tidak',
            'kesimpulan' => 'Sehat untuk bekerja.',
            'anjuran' => 'Olahraga rutin.',
        ];
    }
}
