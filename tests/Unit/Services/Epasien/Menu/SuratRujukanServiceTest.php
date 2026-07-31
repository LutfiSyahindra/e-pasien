<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Repositories\epasien\bridging\RujukanRepository;
use App\Repositories\epasien\menu\Surat\SuratRujukanRepository;
use App\Services\epasien\menu\Surat\SuratRujukanService;
use Mockery;
use Tests\TestCase;

class SuratRujukanServiceTest extends TestCase
{
    public function test_incoming_search_checks_pcare_and_hospital_sources(): void
    {
        $generalRepository = Mockery::mock(SuratRujukanRepository::class);
        $bpjsRepository = Mockery::mock(RujukanRepository::class);
        $bpjsRepository
            ->shouldReceive('findPcareByCardNumber')
            ->once()
            ->with('0002035020396')
            ->andReturn($this->incomingResponse(
                'PC-001',
                '2026-07-01',
                'Puskesmas Sehat'
            ));
        $bpjsRepository
            ->shouldReceive('findHospitalByCardNumber')
            ->once()
            ->with('0002035020396')
            ->andReturn($this->incomingResponse(
                'RS-001',
                '2026-07-02',
                'RS Sehat'
            ));

        $result = (new SuratRujukanService(
            $generalRepository,
            $bpjsRepository
        ))->incomingBpjsForPatient((object) [
            'no_peserta' => '0002 0350 20396',
        ]);

        $this->assertTrue($result['available']);
        $this->assertFalse($result['partial']);
        $this->assertCount(2, $result['rujukan']);
        $this->assertSame('RS-001', $result['rujukan'][0]['no_rujukan']);
        $this->assertSame('rumah_sakit', $result['rujukan'][0]['source']);
        $this->assertSame('success', $result['sources']['pcare']['state']);
        $this->assertSame(
            'success',
            $result['sources']['rumah_sakit']['state']
        );
    }

    public function test_outgoing_vclaim_result_is_limited_to_patient_card(): void
    {
        $generalRepository = Mockery::mock(SuratRujukanRepository::class);
        $bpjsRepository = Mockery::mock(RujukanRepository::class);
        $bpjsRepository
            ->shouldReceive('listOutgoingHospitalReferrals')
            ->once()
            ->with('2026-07-01', '2026-07-31')
            ->andReturn([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => [
                    'list' => [
                        [
                            'noRujukan' => 'PATIENT-REFERRAL',
                            'tglRujukan' => '2026-07-10',
                            'jnsPelayanan' => '2',
                            'noSep' => 'PATIENT-SEP',
                            'noKartu' => '0002035020396',
                            'nama' => 'Budi',
                            'ppkDirujuk' => '0123R001',
                            'namaPpkDirujuk' => 'RS Tujuan',
                        ],
                        [
                            'noRujukan' => 'OTHER-REFERRAL',
                            'tglRujukan' => '2026-07-11',
                            'jnsPelayanan' => '1',
                            'noSep' => 'OTHER-SEP',
                            'noKartu' => '9999999999999',
                            'nama' => 'Pasien Lain',
                            'ppkDirujuk' => '0999R001',
                            'namaPpkDirujuk' => 'RS Lain',
                        ],
                    ],
                ],
            ]);

        $result = (new SuratRujukanService(
            $generalRepository,
            $bpjsRepository
        ))->bpjsOutgoingForPatient(
            (object) ['no_peserta' => '0002035020396'],
            '2026-07-01',
            '2026-07-31'
        );

        $this->assertCount(1, $result['rujukan']);
        $this->assertSame(
            'PATIENT-REFERRAL',
            $result['rujukan'][0]['no_rujukan']
        );
        $this->assertSame(
            'Rawat Jalan',
            $result['rujukan'][0]['jenis_pelayanan_label']
        );
        $this->assertSame('•••••••••0396', $result['masked_card_number']);
    }

    public function test_missing_card_number_never_calls_vclaim(): void
    {
        $generalRepository = Mockery::mock(SuratRujukanRepository::class);
        $bpjsRepository = Mockery::mock(RujukanRepository::class);
        $bpjsRepository->shouldNotReceive('findPcareByCardNumber');
        $bpjsRepository->shouldNotReceive('findHospitalByCardNumber');

        $result = (new SuratRujukanService(
            $generalRepository,
            $bpjsRepository
        ))->incomingBpjsForPatient((object) ['no_peserta' => '']);

        $this->assertFalse($result['available']);
        $this->assertSame('422', $result['meta_data']['code']);
    }

    public function test_outgoing_success_for_another_patient_becomes_empty_result(): void
    {
        $generalRepository = Mockery::mock(SuratRujukanRepository::class);
        $bpjsRepository = Mockery::mock(RujukanRepository::class);
        $bpjsRepository
            ->shouldReceive('listOutgoingHospitalReferrals')
            ->once()
            ->andReturn([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => [
                    'list' => [[
                        'noRujukan' => 'OTHER-REFERRAL',
                        'noKartu' => '9999999999999',
                    ]],
                ],
            ]);

        $result = (new SuratRujukanService(
            $generalRepository,
            $bpjsRepository
        ))->bpjsOutgoingForPatient(
            (object) ['no_peserta' => '0002035020396'],
            '2026-07-01',
            '2026-07-31'
        );

        $this->assertSame([], $result['rujukan']);
        $this->assertSame('204', $result['meta_data']['code']);
        $this->assertSame(
            'Rujukan keluar BPJS tidak ditemukan pada rentang tanggal ini.',
            $result['meta_data']['message']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function incomingResponse(
        string $number,
        string $date,
        string $provider
    ): array {
        return [
            'metaData' => ['code' => '200', 'message' => 'Sukses'],
            'response' => [
                'rujukan' => [
                    'noKunjungan' => $number,
                    'tglKunjungan' => $date,
                    'keluhan' => 'Kontrol',
                    'diagnosa' => ['kode' => 'A01', 'nama' => 'Diagnosis'],
                    'pelayanan' => ['kode' => '2', 'nama' => 'Rawat Jalan'],
                    'poliRujukan' => ['kode' => 'INT', 'nama' => 'Poli Dalam'],
                    'provPerujuk' => ['kode' => '001', 'nama' => $provider],
                    'peserta' => [
                        'nama' => 'Budi',
                        'statusPeserta' => [
                            'kode' => '0',
                            'keterangan' => 'AKTIF',
                        ],
                    ],
                ],
            ],
        ];
    }
}
