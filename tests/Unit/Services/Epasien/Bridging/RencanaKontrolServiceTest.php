<?php

namespace Tests\Unit\Services\Epasien\Bridging;

use App\Repositories\epasien\bridging\RencanaKontrolRepository;
use App\Repositories\epasien\bridging\RujukanRepository;
use App\Services\epasien\bridging\RencanaKontrolService;
use PHPUnit\Framework\TestCase;

class RencanaKontrolServiceTest extends TestCase
{
    public function test_it_formats_the_complete_control_letter_detail(): void
    {
        $repository = $this->createMock(RencanaKontrolRepository::class);
        $repository
            ->expects($this->once())
            ->method('findByControlLetterNumber')
            ->with('0301R0111125K000002')
            ->willReturn([
                'response' => [
                    'noSuratKontrol' => '0301R0111125K000002',
                    'tglRencanaKontrol' => '2025-11-25',
                    'tglTerbit' => '2025-11-18',
                    'jnsKontrol' => '2',
                    'poliTujuan' => 'BED',
                    'namaPoliTujuan' => 'BEDAH',
                    'kodeDokter' => '31348',
                    'namaDokter' => 'DOKTER BEDAH',
                    'flagKontrol' => 'False',
                    'kodeDokterPembuat' => '31348',
                    'namaDokterPembuat' => 'DOKTER BEDAH',
                    'namaJnsKontrol' => 'Kontrol',
                    'sep' => [
                        'noSep' => '0301R0110725V000006',
                        'tglSep' => '2025-07-30',
                        'jnsPelayanan' => 'Rawat Jalan',
                        'poli' => 'BED - BEDAH',
                        'diagnosa' => 'E10 - Insulin-dependent diabetes mellitus',
                        'peserta' => [
                            'noKartu' => '0002482505324',
                            'nama' => 'PASIEN UJI',
                            'tglLahir' => '1983-09-07',
                            'kelamin' => 'P',
                            'hakKelas' => '-',
                        ],
                        'provUmum' => [
                            'kdProvider' => '10210901',
                            'nmProvider' => 'KERTASEMAYA',
                        ],
                        'provPerujuk' => [
                            'kdProviderPerujuk' => '0050B107',
                            'nmProviderPerujuk' => 'Klinik Sehat Gajah Mada',
                            'asalRujukan' => '1',
                            'noRujukan' => '0050B1070924P000001',
                            'tglRujukan' => '2025-10-01',
                        ],
                    ],
                    'formPRB' => [
                        'kdStatusPRB' => '1',
                        'data' => [
                            'HBA1C' => '6.5',
                            'GDP' => null,
                        ],
                    ],
                ],
                'metaData' => [
                    'code' => '200',
                    'message' => 'Sukses',
                ],
            ]);

        $result = (new RencanaKontrolService($repository, $this->referralRepository()))
            ->detailByControlLetterNumber(' 0301R0111125K000002 ');

        $this->assertSame('200', $result['meta_data']['code']);
        $this->assertSame(
            '0301R0111125K000002',
            $result['surat_kontrol']['no_surat_kontrol']
        );
        $this->assertSame(
            '0301R0110725V000006',
            $result['surat_kontrol']['sep']['no_sep']
        );
        $this->assertSame(
            'PASIEN UJI',
            $result['surat_kontrol']['sep']['peserta']['nama']
        );
        $this->assertSame(
            'Klinik Sehat Gajah Mada',
            $result['surat_kontrol']['sep']['provider_perujuk']['nama_provider']
        );
        $this->assertSame(
            '6.5',
            $result['surat_kontrol']['form_prb']['data']['HBA1C']
        );
        $this->assertNull($result['surat_kontrol']['form_prb']['data']['GDP']);
    }

    public function test_spri_detail_accepts_an_empty_sep_reference(): void
    {
        $repository = $this->createStub(RencanaKontrolRepository::class);
        $repository->method('findByControlLetterNumber')->willReturn([
            'response' => [
                'noSuratKontrol' => '0301R0111125K000003',
                'jnsKontrol' => '1',
                'namaJnsKontrol' => 'SPRI',
                'sep' => [],
                'formPRB' => null,
            ],
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
        ]);

        $result = (new RencanaKontrolService($repository, $this->referralRepository()))
            ->detailByControlLetterNumber('0301R0111125K000003');

        $this->assertSame('1', $result['surat_kontrol']['jenis_kontrol']);
        $this->assertNull($result['surat_kontrol']['sep']);
        $this->assertNull($result['surat_kontrol']['form_prb']);
    }

    public function test_it_searches_previous_month_first_then_registration_month(): void
    {
        $calls = [];
        $repository = $this->createMock(RencanaKontrolRepository::class);
        $repository
            ->expects($this->exactly(2))
            ->method('listByCardNumber')
            ->willReturnCallback(function (
                string $month,
                string $year,
                string $cardNumber,
                int $filter
            ) use (&$calls): array {
                $calls[] = [$month, $year, $cardNumber, $filter];

                if ($month === '06') {
                    return [
                        'metaData' => [
                            'code' => '201',
                            'message' => 'Data tidak ditemukan.',
                        ],
                        'response' => null,
                    ];
                }

                return [
                    'metaData' => [
                        'code' => '200',
                        'message' => 'Sukses',
                    ],
                    'response' => [
                        'list' => [[
                            'noSuratKontrol' => '0117R0770122K000004',
                            'jnsPelayanan' => 'Rawat Inap',
                            'jnsKontrol' => '2',
                            'namaJnsKontrol' => 'Surat Kontrol',
                            'tglRencanaKontrol' => '2026-07-29',
                            'tglTerbitKontrol' => '2026-07-27',
                            'noSepAsalKontrol' => '0117R0770122V000003',
                            'poliAsal' => 'INT',
                            'namaPoliAsal' => '-',
                            'poliTujuan' => 'INT',
                            'namaPoliTujuan' => 'PENYAKIT DALAM',
                            'tglSEP' => '2026-07-26',
                            'kodeDokter' => '296676',
                            'namaDokter' => 'ABD KADIR',
                            'noKartu' => '0002035874204',
                            'nama' => 'ANI AZKIA',
                            'terbitSEP' => 'Belum',
                        ]],
                    ],
                ];
            });

        $result = (new RencanaKontrolService($repository, $this->referralRepository()))
            ->listByCardNumber('2026-07-29', ' 0002035874204 ', 2);

        $this->assertSame([
            ['06', '2026', '0002035874204', 2],
            ['07', '2026', '0002035874204', 2],
        ], $calls);
        $this->assertSame('200', $result['meta_data']['code']);
        $this->assertSame('06', $result['periode']['bulan_awal']);
        $this->assertSame('07', $result['periode']['bulan_akhir']);
        $this->assertSame('Juni 2026 dan Juli 2026', $result['periode']['label']);
        $this->assertCount(2, $result['periode_pencarian']);
        $this->assertSame(2, $result['filter']);
        $this->assertCount(1, $result['surat_kontrol']);
        $this->assertSame(
            '0117R0770122K000004',
            $result['surat_kontrol'][0]['no_surat_kontrol']
        );
        $this->assertSame(
            'PENYAKIT DALAM',
            $result['surat_kontrol'][0]['nama_poli_tujuan']
        );
        $this->assertSame('Belum', $result['surat_kontrol'][0]['terbit_sep']);
    }

    public function test_data_not_found_response_produces_an_empty_list(): void
    {
        $repository = $this->createStub(RencanaKontrolRepository::class);
        $repository->method('listByCardNumber')->willReturn([
            'metaData' => [
                'code' => '201',
                'message' => 'Data tidak ditemukan.',
            ],
            'response' => null,
        ]);

        $result = (new RencanaKontrolService($repository, $this->referralRepository()))
            ->listByCardNumber('2026-07-29', '0002035874204');

        $this->assertSame('201', $result['meta_data']['code']);
        $this->assertSame([], $result['surat_kontrol']);
    }

    public function test_technical_failure_stops_the_multi_month_search(): void
    {
        $repository = $this->createMock(RencanaKontrolRepository::class);
        $repository
            ->expects($this->once())
            ->method('listByCardNumber')
            ->willReturn([
                'metaData' => [
                    'code' => 504,
                    'message' => 'Tidak dapat terhubung ke layanan VClaim BPJS.',
                ],
            ]);

        $result = (new RencanaKontrolService($repository, $this->referralRepository()))
            ->listByCardNumber('2026-07-29', '0002035874204');

        $this->assertSame('504', $result['meta_data']['code']);
        $this->assertCount(1, $result['periode_pencarian']);
        $this->assertSame([], $result['surat_kontrol']);
    }

    public function test_previous_month_search_handles_year_rollover(): void
    {
        $calls = [];
        $repository = $this->createStub(RencanaKontrolRepository::class);
        $repository
            ->method('listByCardNumber')
            ->willReturnCallback(function (string $month, string $year) use (&$calls): array {
                $calls[] = [$month, $year];

                return [
                    'metaData' => [
                        'code' => '201',
                        'message' => 'Data tidak ditemukan.',
                    ],
                    'response' => null,
                ];
            });

        (new RencanaKontrolService($repository, $this->referralRepository()))
            ->listByCardNumber('2026-01-10', '0002035874204');

        $this->assertSame([
            ['12', '2025'],
            ['01', '2026'],
        ], $calls);
    }

    public function test_it_can_search_a_custom_seven_month_window(): void
    {
        $calls = [];
        $repository = $this->createStub(RencanaKontrolRepository::class);
        $repository
            ->method('listByCardNumber')
            ->willReturnCallback(function (string $month, string $year) use (&$calls): array {
                $calls[] = [$month, $year];

                return [
                    'metaData' => [
                        'code' => '201',
                        'message' => 'Data tidak ditemukan.',
                    ],
                    'response' => null,
                ];
            });

        $result = (new RencanaKontrolService($repository, $this->referralRepository()))
            ->listByCardNumber(
                '2026-07-29',
                '0002035874204',
                2,
                false,
                3,
                3
            );

        $this->assertSame([
            ['04', '2026'],
            ['05', '2026'],
            ['06', '2026'],
            ['07', '2026'],
            ['08', '2026'],
            ['09', '2026'],
            ['10', '2026'],
        ], $calls);
        $this->assertSame('04', $result['periode']['bulan_awal']);
        $this->assertSame('10', $result['periode']['bulan_akhir']);
        $this->assertSame(7, $result['periode']['jumlah_bulan']);
        $this->assertSame(
            'April 2026 sampai Oktober 2026',
            $result['periode']['label']
        );
    }

    public function test_it_falls_back_to_pcare_when_all_control_letters_have_issued_sep(): void
    {
        $controlLetterRepository = $this->createStub(RencanaKontrolRepository::class);
        $controlLetterRepository->method('listByCardNumber')->willReturn([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => [
                'list' => [
                    [
                        'noSuratKontrol' => '0117R0770122K000004',
                        'terbitSEP' => 'Sudah',
                    ],
                    [
                        'noSuratKontrol' => '0117R0770122K000005',
                        'terbitSEP' => 'sudah terbit',
                    ],
                ],
            ],
        ]);

        $referralRepository = $this->createMock(RujukanRepository::class);
        $referralRepository
            ->expects($this->once())
            ->method('findPcareByCardNumber')
            ->with('0000416382632')
            ->willReturn($this->referralResponse(
                '030107010217Y001465',
                'SITEBA',
                'Hyperplasia of prostate'
            ));
        $referralRepository
            ->expects($this->never())
            ->method('findHospitalByCardNumber');

        $result = (new RencanaKontrolService(
            $controlLetterRepository,
            $referralRepository
        ))->listByCardNumber('2026-07-29', '0000416382632');

        $this->assertTrue($result['semua_surat_kontrol_sep_terbit']);
        $this->assertCount(2, $result['surat_kontrol']);
        $this->assertSame('rujukan_pcare', $result['sumber_dokumen']);
        $this->assertSame(
            '030107010217Y001465',
            $result['rujukan']['no_rujukan']
        );
    }

    public function test_it_falls_back_to_hospital_when_all_control_letters_have_issued_sep_and_pcare_is_empty(): void
    {
        $controlLetterRepository = $this->createStub(RencanaKontrolRepository::class);
        $controlLetterRepository->method('listByCardNumber')->willReturn([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => [
                'list' => [[
                    'noSuratKontrol' => '0117R0770122K000004',
                    'terbitSEP' => 'Sudah',
                ]],
            ],
        ]);

        $referralRepository = $this->createMock(RujukanRepository::class);
        $referralRepository
            ->expects($this->once())
            ->method('findPcareByCardNumber')
            ->with('0105986780439')
            ->willReturn([
                'metaData' => [
                    'code' => '201',
                    'message' => 'Rujukan tidak ditemukan.',
                ],
                'response' => null,
            ]);
        $referralRepository
            ->expects($this->once())
            ->method('findHospitalByCardNumber')
            ->with('0105986780439')
            ->willReturn($this->referralResponse(
                '0304R0050217A000079',
                'RSI IBNU SINA',
                'Acute myocardial infarction, unspecified'
            ));

        $result = (new RencanaKontrolService(
            $controlLetterRepository,
            $referralRepository
        ))->listByCardNumber('2026-07-29', '0105986780439');

        $this->assertTrue($result['semua_surat_kontrol_sep_terbit']);
        $this->assertSame('rujukan_rumah_sakit', $result['sumber_dokumen']);
        $this->assertSame(
            '0304R0050217A000079',
            $result['rujukan']['no_rujukan']
        );
    }

    public function test_it_does_not_fall_back_when_a_control_letter_has_not_issued_sep(): void
    {
        $controlLetterRepository = $this->createStub(RencanaKontrolRepository::class);
        $controlLetterRepository->method('listByCardNumber')->willReturn([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => [
                'list' => [
                    [
                        'noSuratKontrol' => '0117R0770122K000004',
                        'terbitSEP' => 'Sudah',
                    ],
                    [
                        'noSuratKontrol' => '0117R0770122K000005',
                        'terbitSEP' => 'Belum',
                    ],
                ],
            ],
        ]);

        $referralRepository = $this->createMock(RujukanRepository::class);
        $referralRepository
            ->expects($this->never())
            ->method('findPcareByCardNumber');
        $referralRepository
            ->expects($this->never())
            ->method('findHospitalByCardNumber');

        $result = (new RencanaKontrolService(
            $controlLetterRepository,
            $referralRepository
        ))->listByCardNumber('2026-07-29', '0000416382632');

        $this->assertFalse($result['semua_surat_kontrol_sep_terbit']);
        $this->assertSame('surat_kontrol', $result['sumber_dokumen']);
        $this->assertNull($result['rujukan']);
    }

    public function test_it_falls_back_to_pcare_when_control_letter_is_not_found(): void
    {
        $controlLetterRepository = $this->createStub(RencanaKontrolRepository::class);
        $controlLetterRepository->method('listByCardNumber')->willReturn([
            'metaData' => [
                'code' => '201',
                'message' => 'Data tidak ditemukan.',
            ],
            'response' => null,
        ]);

        $referralRepository = $this->createMock(RujukanRepository::class);
        $referralRepository
            ->expects($this->once())
            ->method('findPcareByCardNumber')
            ->with('0000416382632')
            ->willReturn($this->referralResponse(
                '030107010217Y001465',
                'SITEBA',
                'Hyperplasia of prostate'
            ));
        $referralRepository
            ->expects($this->never())
            ->method('findHospitalByCardNumber');

        $result = (new RencanaKontrolService(
            $controlLetterRepository,
            $referralRepository
        ))->listByCardNumber('2026-07-29', ' 0000416382632 ');

        $this->assertSame('200', $result['meta_data']['code']);
        $this->assertSame('rujukan_pcare', $result['sumber_dokumen']);
        $this->assertSame([], $result['surat_kontrol']);
        $this->assertSame(
            '030107010217Y001465',
            $result['rujukan']['no_rujukan']
        );
        $this->assertSame(
            'Hyperplasia of prostate',
            $result['rujukan']['diagnosa']['nama']
        );
        $this->assertSame(
            'SITEBA',
            $result['rujukan']['provider_perujuk']['nama']
        );
        $this->assertNull($result['pencarian_rujukan']['rumah_sakit']);
    }

    public function test_it_falls_back_to_hospital_when_pcare_referral_is_not_found(): void
    {
        $controlLetterRepository = $this->createStub(RencanaKontrolRepository::class);
        $controlLetterRepository->method('listByCardNumber')->willReturn([
            'metaData' => [
                'code' => '201',
                'message' => 'Data tidak ditemukan.',
            ],
            'response' => null,
        ]);

        $referralRepository = $this->createMock(RujukanRepository::class);
        $referralRepository
            ->expects($this->once())
            ->method('findPcareByCardNumber')
            ->with('0105986780439')
            ->willReturn([
                'metaData' => [
                    'code' => '201',
                    'message' => 'Rujukan tidak ditemukan.',
                ],
                'response' => null,
            ]);
        $referralRepository
            ->expects($this->once())
            ->method('findHospitalByCardNumber')
            ->with('0105986780439')
            ->willReturn($this->referralResponse(
                '0304R0050217A000079',
                'RSI IBNU SINA',
                'Acute myocardial infarction, unspecified'
            ));

        $result = (new RencanaKontrolService(
            $controlLetterRepository,
            $referralRepository
        ))->listByCardNumber('2026-07-29', '0105986780439');

        $this->assertSame('200', $result['meta_data']['code']);
        $this->assertSame('rujukan_rumah_sakit', $result['sumber_dokumen']);
        $this->assertSame(
            '0304R0050217A000079',
            $result['rujukan']['no_rujukan']
        );
        $this->assertSame(
            'RSI IBNU SINA',
            $result['rujukan']['provider_perujuk']['nama']
        );
        $this->assertSame(
            '201',
            $result['pencarian_rujukan']['pcare']['code']
        );
    }

    public function test_it_formats_multiple_referrals_as_selectable_results(): void
    {
        $controlLetterRepository = $this->createStub(RencanaKontrolRepository::class);
        $controlLetterRepository->method('listByCardNumber')->willReturn([
            'metaData' => [
                'code' => '201',
                'message' => 'Data tidak ditemukan.',
            ],
            'response' => null,
        ]);

        $firstReferral = $this->referralResponse(
            '030107010217Y001465',
            'SITEBA',
            'Hyperplasia of prostate'
        )['response']['rujukan'];
        $secondReferral = $this->referralResponse(
            '030107010217Y001466',
            'SITEBA',
            'Urinary tract infection'
        )['response']['rujukan'];

        $referralRepository = $this->createMock(RujukanRepository::class);
        $referralRepository
            ->expects($this->once())
            ->method('findPcareByCardNumber')
            ->willReturn([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'rujukan' => [$firstReferral, $secondReferral],
                ],
            ]);
        $referralRepository
            ->expects($this->never())
            ->method('findHospitalByCardNumber');

        $result = (new RencanaKontrolService(
            $controlLetterRepository,
            $referralRepository
        ))->listByCardNumber('2026-07-29', '0000416382632');

        $this->assertCount(2, $result['daftar_rujukan']);
        $this->assertSame(
            '030107010217Y001465',
            $result['rujukan']['no_rujukan']
        );
        $this->assertSame(
            '030107010217Y001466',
            $result['daftar_rujukan'][1]['no_rujukan']
        );
    }

    public function test_it_does_not_hide_a_pcare_service_error_with_hospital_fallback(): void
    {
        $controlLetterRepository = $this->createStub(RencanaKontrolRepository::class);
        $controlLetterRepository->method('listByCardNumber')->willReturn([
            'metaData' => [
                'code' => '201',
                'message' => 'Data tidak ditemukan.',
            ],
            'response' => null,
        ]);

        $referralRepository = $this->createMock(RujukanRepository::class);
        $referralRepository
            ->expects($this->once())
            ->method('findPcareByCardNumber')
            ->willReturn([
                'metaData' => [
                    'code' => '500',
                    'message' => 'Layanan PCare bermasalah.',
                ],
            ]);
        $referralRepository
            ->expects($this->never())
            ->method('findHospitalByCardNumber');

        $result = (new RencanaKontrolService(
            $controlLetterRepository,
            $referralRepository
        ))->listByCardNumber('2026-07-29', '0105986780439');

        $this->assertSame('500', $result['meta_data']['code']);
        $this->assertNull($result['rujukan']);
        $this->assertNull($result['sumber_dokumen']);
    }

    private function referralRepository(): RujukanRepository
    {
        $repository = $this->createStub(RujukanRepository::class);
        $repository->method('findPcareByCardNumber')->willReturn([
            'metaData' => [
                'code' => '201',
                'message' => 'Rujukan tidak ditemukan.',
            ],
            'response' => null,
        ]);
        $repository->method('findHospitalByCardNumber')->willReturn([
            'metaData' => [
                'code' => '201',
                'message' => 'Rujukan tidak ditemukan.',
            ],
            'response' => null,
        ]);

        return $repository;
    }

    /**
     * @return array<string, mixed>
     */
    private function referralResponse(
        string $referralNumber,
        string $providerName,
        string $diagnosisName
    ): array {
        return [
            'metaData' => [
                'code' => '200',
                'message' => 'OK',
            ],
            'response' => [
                'rujukan' => [
                    'diagnosa' => [
                        'kode' => 'N40',
                        'nama' => $diagnosisName,
                    ],
                    'keluhan' => 'Keluhan pasien',
                    'noKunjungan' => $referralNumber,
                    'pelayanan' => [
                        'kode' => '2',
                        'nama' => 'Rawat Jalan',
                    ],
                    'peserta' => [
                        'hakKelas' => [
                            'keterangan' => 'KELAS I',
                            'kode' => '1',
                        ],
                        'jenisPeserta' => [
                            'keterangan' => 'PENERIMA PENSIUN PNS',
                            'kode' => '15',
                        ],
                        'mr' => [
                            'noMR' => '298036',
                            'noTelepon' => null,
                        ],
                        'nama' => 'PASIEN RUJUKAN',
                        'nik' => null,
                        'noKartu' => '0000416382632',
                        'provUmum' => [
                            'kdProvider' => '03010701',
                            'nmProvider' => 'SITEBA',
                        ],
                        'sex' => 'L',
                        'statusPeserta' => [
                            'keterangan' => 'AKTIF',
                            'kode' => '0',
                        ],
                        'tglLahir' => '1938-08-31',
                    ],
                    'poliRujukan' => [
                        'kode' => 'URO',
                        'nama' => 'UROLOGI',
                    ],
                    'provPerujuk' => [
                        'kode' => '03010701',
                        'nama' => $providerName,
                    ],
                    'tglKunjungan' => '2017-02-25',
                ],
            ],
        ];
    }
}
