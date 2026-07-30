<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\bridging\RencanaKontrolService;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\settings\RegistrationRoleConfigurationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class DaftarOnlinePageTest extends TestCase
{
    public function test_new_registration_page_shows_guarantor_notice_and_visit_fields(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_tlp' => '08123456789',
            'alamat' => 'Jl. Sehat',
        ];

        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($patient): void {
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock->shouldReceive('pendingRegistrationForMedicalRecord')->once()->with('000123')->andReturnNull();
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([
                ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS Kesehatan'],
                ['kd_pj' => 'A01', 'png_jawab' => 'Asuransi Sehat'],
            ]);
        });

        $user = new User([
            'name' => 'Budi',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.index'));

        $response
            ->assertOk()
            ->assertSee('id="onlineRegistrationNoticeModal"', false)
            ->assertSeeText('UMUM')
            ->assertSeeText('Asuransi selain BPJS Kesehatan')
            ->assertSeeText('Dapat didaftarkan langsung tanpa surat kontrol atau rujukan')
            ->assertSeeText('BPJS Kesehatan tersedia, kecuali untuk poli IRM.')
            ->assertSeeText('Disimpan bersama pendaftaran BPJ langsung ke reg_periksa.')
            ->assertSee('class="online-choice-flow"', false)
            ->assertSee('id="kd_poli"', false)
            ->assertSee('id="kd_dokter"', false)
            ->assertSee('id="kd_pj"', false)
            ->assertSeeText('Pilih poliklinik sebelum memilih dokter.')
            ->assertSee('const mappedName = String(schedule?.nm_poli_bpjs || \'\')', false)
            ->assertSee('label: `${displayName} (${bpjsCode})`', false)
            ->assertSee('dropdownParent: field', false)
            ->assertSee('showBootstrapModal(elements.noticeModal);', false)
            ->assertDontSee('id="mjknRegistrationWizardModal"', false);
    }

    public function test_active_visit_hides_patient_data_and_visit_summary_cards(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_tlp' => '08123456789',
            'alamat' => 'Jl. Sehat',
            'tgl_lahir' => '1990-05-17',
        ];
        $pendingRegistration = [
            'status' => 'Belum',
            'status_bayar' => 'Belum Bayar',
            'poli' => 'Poli Umum',
            'dokter' => 'dr. Sehat',
            'no_reg' => '001',
            'no_rawat' => '2026/07/28/000001',
            'hari' => 'Selasa',
            'tanggal_label' => '28 Juli 2026',
            'jam' => '08:00',
            'penjamin' => 'Umum',
            'can_cancel' => true,
        ];

        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($patient, $pendingRegistration): void {
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock
                ->shouldReceive('pendingRegistrationForMedicalRecord')
                ->once()
                ->with('000123')
                ->andReturn($pendingRegistration);
            $mock->shouldNotReceive('penjaminOptions');
        });

        $user = new User([
            'name' => 'Budi',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.index'));

        $response
            ->assertOk()
            ->assertSeeText('Kunjungan Aktif')
            ->assertSee('class="online-layout single-panel"', false)
            ->assertSee('id="cancelPendingRegistration"', false)
            ->assertSeeText('Batal Pendaftaran')
            ->assertSee('cancelUrl:', false)
            ->assertSee('showNotice: false', false)
            ->assertSee('if (config.showNotice && !config.hasPendingRegistration)', false)
            ->assertDontSee('class="online-side-panel"', false)
            ->assertDontSeeText('Data Pasien')
            ->assertDontSeeText('Ringkasan Kunjungan');
    }

    public function test_active_visit_hides_cancel_button_after_patient_checkin(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_tlp' => '08123456789',
            'alamat' => 'Jl. Sehat',
            'tgl_lahir' => '1990-05-17',
        ];
        $pendingRegistration = [
            'status' => 'Belum',
            'status_bayar' => 'Belum Bayar',
            'poli' => 'Poli Umum',
            'dokter' => 'dr. Sehat',
            'no_reg' => '001',
            'no_rawat' => '2026/07/28/000001',
            'hari' => 'Selasa',
            'tanggal_label' => '28 Juli 2026',
            'jam' => '08:00',
            'penjamin' => 'Umum',
            'sudah_checkin' => true,
            'can_cancel' => false,
        ];

        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($patient, $pendingRegistration): void {
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock
                ->shouldReceive('pendingRegistrationForMedicalRecord')
                ->once()
                ->with('000123')
                ->andReturn($pendingRegistration);
            $mock->shouldNotReceive('penjaminOptions');
        });

        $user = new User([
            'name' => 'Budi',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.index'));

        $response
            ->assertOk()
            ->assertSeeText('Kunjungan Aktif')
            ->assertDontSee('id="cancelPendingRegistration"', false);
    }

    public function test_configured_registration_role_can_select_patient_and_bpjs_guarantor(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000456',
            'nm_pasien' => 'Siti',
            'no_tlp' => '08120000000',
            'alamat' => 'Jl. Mawar',
            'tgl_lahir' => '1990-05-17',
            'no_peserta' => '0001234567890',
        ];

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($patient): void {
            $mock->shouldReceive('patientForMedicalRecord')->once()->with('000456')->andReturn($patient);
            $mock->shouldReceive('pendingRegistrationForMedicalRecord')->once()->with('000456')->andReturnNull();
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([
                ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS Kesehatan'],
            ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.index', [
            'no_rkm_medis' => '000456',
        ]));

        $response
            ->assertOk()
            ->assertSeeText('Role Anda dapat mendaftarkan pasien lain')
            ->assertSeeText('BPJS Kesehatan diproses melalui alur MJKN.')
            ->assertSee('value="000456"', false)
            ->assertSee('id="no_peserta"', false)
            ->assertSee('value="0001234567890"', false)
            ->assertSeeText('Digunakan untuk mencari surat kontrol, lalu rujukan PCare dan RS.')
            ->assertSee('id="mjknRegistrationWizardModal"', false)
            ->assertSee('id="bpjsControlLetterModal"', false)
            ->assertSee('controlLettersUrl:', false)
            ->assertSee('controlLetterDetailUrl:', false)
            ->assertSee('antrolPreviewUrl:', false)
            ->assertSee('antrolSubmitUrl:', false)
            ->assertSeeText('Proses Daftar MJKN')
            ->assertSeeText('1. Cari Dokumen')
            ->assertSeeText('2. Pilih Dokumen')
            ->assertSeeText('3. Data Final')
            ->assertSeeText('Data final yang akan dikirim')
            ->assertSeeText('Lihat Detail')
            ->assertSeeText('Lanjut Lihat Data Final')
            ->assertSeeText('Daftarkan & Kirim ke BPJS')
            ->assertSee('bpjs_document_nik: documentData.nik', false)
            ->assertSee('bpjs_document_clinic_code: documentData.clinicCode', false)
            ->assertDontSee('id="antrolFinalSummary"', false)
            ->assertDontSee('id="antrolPayloadSources"', false)
            ->assertDontSee('class="online-antrol-preview-warning"', false)
            ->assertDontSee('class="online-antrol-preview-request"', false)
            ->assertSeeText('SEP Sudah Terbit')
            ->assertSeeText('SEP Belum Terbit')
            ->assertSeeInOrder(['Data Pasien', 'Ringkasan Kunjungan'])
            ->assertSee('class="online-patient-profile"', false)
            ->assertSee('class="online-patient-meta"', false)
            ->assertSeeText('Terpilih')
            ->assertSeeText('Tanggal Lahir')
            ->assertSeeText('17 Mei 1990')
            ->assertSeeText('Siti');
    }

    public function test_configured_registration_role_can_search_patients_by_name_and_choose_a_result(): void
    {
        $patients = collect([
            (object) [
                'no_rkm_medis' => '000456',
                'nm_pasien' => 'Siti Aminah',
                'tgl_lahir' => '1990-05-17',
            ],
            (object) [
                'no_rkm_medis' => '000789',
                'nm_pasien' => 'Siti Rahma',
                'tgl_lahir' => '1990-05-17',
            ],
        ]);

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($patients): void {
            $mock
                ->shouldReceive('searchPatients')
                ->once()
                ->with('Siti', '1990-05-17')
                ->andReturn($patients);
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.index', [
            'patient_search' => 'Siti',
            'patient_birth_date' => '1990-05-17',
        ]));

        $response
            ->assertOk()
            ->assertSee('name="patient_search"', false)
            ->assertSee('name="patient_birth_date"', false)
            ->assertSee('value="Siti"', false)
            ->assertSee('value="1990-05-17"', false)
            ->assertSeeText('2 hasil ditemukan untuk “Siti” dengan tanggal lahir 17 Mei 1990')
            ->assertSeeText('Pilih salah satu pasien dari hasil pencarian di atas.')
            ->assertSeeText('Siti Aminah')
            ->assertSeeText('No. RM 000456')
            ->assertSeeText('17 Mei 1990')
            ->assertSeeText('Siti Rahma')
            ->assertSeeText('No. RM 000789')
            ->assertSee(route('daftarOnline.index', ['no_rkm_medis' => '000456']), false)
            ->assertSee(route('daftarOnline.index', ['no_rkm_medis' => '000789']), false);
    }

    public function test_name_search_without_birth_date_explains_required_combination(): void
    {
        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('searchPatients')->once()->with('Siti', null)->andReturn(collect());
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.index', [
            'patient_search' => 'Siti',
        ]));

        $response
            ->assertOk()
            ->assertSeeText('Pencarian nama wajib disertai tanggal lahir.')
            ->assertSeeText('Jika mencari berdasarkan nama, pilih tanggal lahir pasien.');
    }

    public function test_configured_registration_role_sees_all_history_and_guarantor_filter(): void
    {
        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('registrationHistory')->once()->andReturn(
                new LengthAwarePaginator([], 0, 8)
            );
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([
                ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS Kesehatan'],
                ['kd_pj' => 'UMU', 'png_jawab' => 'Umum'],
            ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.history'));

        $response
            ->assertOk()
            ->assertSeeText('Semua Pasien')
            ->assertSee('name="kd_pj"', false)
            ->assertSeeText('Semua Penjamin')
            ->assertSeeText('BPJS Kesehatan');
    }

    public function test_bpjs_registration_requires_card_number(): void
    {
        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('register');
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->postJson(route('daftarOnline.store'), [
            'no_rkm_medis' => '000456',
            'tgl_registrasi' => now()->toDateString(),
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('no_peserta');
    }

    public function test_staff_bpjs_registration_with_card_number_must_use_mjkn_flow(): void
    {
        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('register');
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->postJson(route('daftarOnline.store'), [
            'no_rkm_medis' => '000456',
            'tgl_registrasi' => now()->toDateString(),
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
            'no_peserta' => '0002035874204',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kd_pj')
            ->assertJsonPath(
                'errors.kd_pj.0',
                'Pendaftaran BPJ tidak disimpan pada tahap ini. Gunakan modal Proses Daftar MJKN untuk memilih dokumen BPJS dan meninjau payload Antrol.'
            );
    }

    public function test_patient_bpjs_registration_with_card_number_is_saved_directly(): void
    {
        $date = now()->toDateString();

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnFalse();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($date): void {
            $mock
                ->shouldReceive('register')
                ->once()
                ->withArgs(function (User $user, array $data, bool $isRegistrationStaff) use ($date): bool {
                    return $user->username === '000123'
                        && $data['tgl_registrasi'] === $date
                        && $data['kd_poli'] === 'POL01'
                        && $data['kd_pj'] === 'BPJ'
                        && $data['no_peserta'] === '0002035874204'
                        && ! $isRegistrationStaff;
                })
                ->andReturn([
                    'registration' => [
                        'no_reg' => '001',
                        'no_rawat' => now()->format('Y/m/d').'/000001',
                        'kd_pj' => 'BPJ',
                    ],
                ]);
        });

        $user = new User([
            'name' => 'Budi',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->postJson(route('daftarOnline.store'), [
            'tgl_registrasi' => $date,
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
            'no_peserta' => '0002035874204',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.registration.kd_pj', 'BPJ');
    }

    public function test_patient_can_cancel_own_pending_registration(): void
    {
        $treatmentNumber = '2026/07/28/000001';
        $user = new User([
            'name' => 'Budi',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnFalse();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($treatmentNumber, $user): void {
            $mock
                ->shouldReceive('cancelRegistration')
                ->once()
                ->with($user, $treatmentNumber, '000123', false, '')
                ->andReturn([
                    'no_rawat' => $treatmentNumber,
                    'status' => 'Batal',
                    'antrol' => null,
                ]);
        });

        $response = $this->actingAs($user)->patchJson(route('daftarOnline.cancel'), [
            'no_rawat' => $treatmentNumber,
            'no_rkm_medis' => '000123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Pendaftaran berhasil dibatalkan.')
            ->assertJsonPath('data.no_rawat', $treatmentNumber)
            ->assertJsonPath('data.status', 'Batal');
    }

    public function test_jkn_cancellation_returns_combined_success_message(): void
    {
        $treatmentNumber = '2026/07/28/000001';
        $user = new User([
            'name' => 'Budi',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnFalse();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($treatmentNumber, $user): void {
            $mock
                ->shouldReceive('cancelRegistration')
                ->once()
                ->with(
                    $user,
                    $treatmentNumber,
                    '000123',
                    false,
                    'Terjadi perubahan jadwal dokter.'
                )
                ->andReturn([
                    'no_rawat' => $treatmentNumber,
                    'status' => 'Batal',
                    'antrol' => [
                        'cancelled' => true,
                        'booking_code' => '20260728000001',
                    ],
                ]);
        });

        $response = $this->actingAs($user)->patchJson(route('daftarOnline.cancel'), [
            'no_rawat' => $treatmentNumber,
            'no_rkm_medis' => '000123',
            'keterangan' => 'Terjadi perubahan jadwal dokter.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath(
                'message',
                'Pendaftaran dan antrean JKN berhasil dibatalkan.'
            )
            ->assertJsonPath('data.antrol.cancelled', true);
    }

    public function test_authorized_staff_can_preview_antrol_payload_without_saving_or_sending(): void
    {
        $date = now()->toDateString();
        $payload = [
            'kodebooking' => now()->format('Ymd').'000001',
            'jenispasien' => 'JKN',
            'nomorkartu' => '0002035874204',
            'nik' => '3212345678987654',
            'nohp' => '081234567890',
            'kodepoli' => 'ANA',
            'namapoli' => 'Anak',
            'pasienbaru' => 0,
            'norm' => '000456',
            'tanggalperiksa' => $date,
            'kodedokter' => 12345,
            'namadokter' => 'Dr. Hendra',
            'jampraktek' => '08:00-16:00',
            'jeniskunjungan' => 3,
            'nomorreferensi' => '0301R0110726K000001',
            'nomorantrean' => 'ANA-001',
            'angkaantrean' => 1,
            'estimasidilayani' => 1785114300000,
            'sisakuotajkn' => 29,
            'kuotajkn' => 30,
            'sisakuotanonjkn' => 29,
            'kuotanonjkn' => 30,
            'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.',
        ];

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($date, $payload): void {
            $mock->shouldNotReceive('register');
            $mock
                ->shouldReceive('previewAntrolPayload')
                ->once()
                ->with(
                    \Mockery::on(fn (User $user): bool => $user->username === 'PETUGAS01'),
                    [
                        'no_rkm_medis' => '000456',
                        'tgl_registrasi' => $date,
                        'kd_dokter' => 'D001',
                        'kd_poli' => 'POL01',
                        'kd_pj' => 'BPJ',
                        'no_peserta' => '0002035874204',
                        'bpjs_document_type' => 'surat_kontrol',
                        'bpjs_document_source' => 'surat_kontrol',
                        'bpjs_document_number' => '0301R0110726K000001',
                    ],
                    true
                )
                ->andReturn([
                    'endpoint' => 'antrean/add',
                    'method' => 'POST',
                    'preview_only' => true,
                    'payload' => $payload,
                ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->postJson(route('daftarOnline.antrol.preview'), [
            'no_rkm_medis' => '000456',
            'tgl_registrasi' => $date,
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
            'no_peserta' => '0002035874204',
            'bpjs_document_type' => 'surat_kontrol',
            'bpjs_document_source' => 'surat_kontrol',
            'bpjs_document_number' => '0301R0110726K000001',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.preview_only', true)
            ->assertJsonPath('data.method', 'POST')
            ->assertJsonPath('data.endpoint', 'antrean/add')
            ->assertJsonPath('data.payload.kodebooking', $payload['kodebooking'])
            ->assertJsonPath('data.payload.jeniskunjungan', 3)
            ->assertJsonPath('data.payload.nomorreferensi', '0301R0110726K000001');
    }

    public function test_authorized_staff_can_confirm_and_submit_final_mjkn_data(): void
    {
        $date = now()->toDateString();
        $previewHash = str_repeat('a', 64);
        $submittedData = [
            'no_rkm_medis' => '000456',
            'tgl_registrasi' => $date,
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
            'no_peserta' => '0002035874204',
            'bpjs_document_type' => 'surat_kontrol',
            'bpjs_document_source' => 'surat_kontrol',
            'bpjs_document_number' => '0301R0110726K000001',
        ];

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use (
            $submittedData,
            $previewHash
        ): void {
            $mock
                ->shouldReceive('registerMjkn')
                ->once()
                ->with(
                    \Mockery::on(fn (User $user): bool => $user->username === 'PETUGAS01'),
                    $submittedData,
                    $previewHash,
                    true
                )
                ->andReturn([
                    'registration' => [
                        'no_reg' => '001',
                        'no_rawat' => '2026/07/28/000001',
                    ],
                    'antrol' => [
                        'sent' => true,
                        'code' => '200',
                        'message' => 'Ok',
                        'booking_code' => '20260728000001',
                        'delivery_status' => 'Sudah',
                    ],
                ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->postJson(
            route('daftarOnline.antrol.submit'),
            array_merge($submittedData, ['preview_hash' => $previewHash])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.registration.no_reg', '001')
            ->assertJsonPath('data.antrol.sent', true)
            ->assertJsonPath('data.antrol.delivery_status', 'Sudah');
    }

    public function test_patient_without_configured_role_cannot_submit_mjkn_registration(): void
    {
        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnFalse();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('registerMjkn');
        });
        $user = new User([
            'name' => 'Pasien',
            'username' => '000123',
            'email' => 'pasien@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->postJson(
            route('daftarOnline.antrol.submit')
        );

        $response
            ->assertForbidden()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath(
                'message',
                'Anda tidak memiliki akses untuk memproses pendaftaran MJKN.'
            );
    }

    public function test_authorized_staff_can_find_bpjs_control_letters_without_saving_registration(): void
    {
        $date = now()->toDateString();

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(RencanaKontrolService::class, function (MockInterface $mock) use ($date): void {
            $mock
                ->shouldReceive('listByCardNumber')
                ->once()
                ->with($date, '0002035874204', 2)
                ->andReturn([
                    'meta_data' => [
                        'code' => '200',
                        'message' => 'Sukses',
                    ],
                    'periode' => [
                        'bulan_awal' => now()->copy()->subMonthNoOverflow()->format('m'),
                        'tahun_awal' => now()->copy()->subMonthNoOverflow()->format('Y'),
                        'bulan_akhir' => now()->format('m'),
                        'tahun_akhir' => now()->format('Y'),
                        'label' => now()->copy()->subMonthNoOverflow()->locale('id')->translatedFormat('F Y')
                            .' dan '.now()->locale('id')->translatedFormat('F Y'),
                    ],
                    'filter' => 2,
                    'surat_kontrol' => [[
                        'no_surat_kontrol' => '0117R0770122K000004',
                        'nama' => 'ANI AZKIA',
                    ]],
                ]);
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('register');
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->getJson(route('daftarOnline.suratKontrol', [
            'tgl_registrasi' => $date,
            'no_peserta' => '0002035874204',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', '1 surat kontrol ditemukan.')
            ->assertJsonPath(
                'data.surat_kontrol.0.no_surat_kontrol',
                '0117R0770122K000004'
            );
    }

    public function test_control_letter_search_returns_pcare_referral_fallback(): void
    {
        $date = now()->toDateString();

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(RencanaKontrolService::class, function (MockInterface $mock) use ($date): void {
            $mock
                ->shouldReceive('listByCardNumber')
                ->once()
                ->with($date, '0000416382632', 2)
                ->andReturn([
                    'meta_data' => [
                        'code' => '200',
                        'message' => 'OK',
                    ],
                    'periode' => [
                        'label' => 'Juni 2026 dan Juli 2026',
                    ],
                    'filter' => 2,
                    'surat_kontrol' => [],
                    'rujukan' => [
                        'no_rujukan' => '030107010217Y001465',
                        'peserta' => [
                            'no_kartu' => '0000416382632',
                            'nama' => 'MUSDIWAR,BA',
                        ],
                    ],
                    'sumber_dokumen' => 'rujukan_pcare',
                ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->getJson(route('daftarOnline.suratKontrol', [
            'tgl_registrasi' => $date,
            'no_peserta' => '0000416382632',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Rujukan PCare ditemukan.')
            ->assertJsonPath('data.sumber_dokumen', 'rujukan_pcare')
            ->assertJsonPath(
                'data.rujukan.no_rujukan',
                '030107010217Y001465'
            );
    }

    public function test_control_letter_search_reports_multiple_referral_choices(): void
    {
        $date = now()->toDateString();

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(RencanaKontrolService::class, function (MockInterface $mock) use ($date): void {
            $mock
                ->shouldReceive('listByCardNumber')
                ->once()
                ->with($date, '0000416382632', 2)
                ->andReturn([
                    'meta_data' => [
                        'code' => '200',
                        'message' => 'OK',
                    ],
                    'surat_kontrol' => [],
                    'rujukan' => [
                        'no_rujukan' => '030107010217Y001465',
                    ],
                    'daftar_rujukan' => [
                        ['no_rujukan' => '030107010217Y001465'],
                        ['no_rujukan' => '030107010217Y001466'],
                    ],
                    'sumber_dokumen' => 'rujukan_pcare',
                ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->getJson(route('daftarOnline.suratKontrol', [
            'tgl_registrasi' => $date,
            'no_peserta' => '0000416382632',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('message', '2 rujukan PCare ditemukan.')
            ->assertJsonCount(2, 'data.daftar_rujukan');
    }

    public function test_authorized_staff_can_view_a_bpjs_control_letter_detail(): void
    {
        $controlLetterNumber = '0301R0111125K000002';

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(RencanaKontrolService::class, function (MockInterface $mock) use ($controlLetterNumber): void {
            $mock
                ->shouldReceive('detailByControlLetterNumber')
                ->once()
                ->with($controlLetterNumber)
                ->andReturn([
                    'meta_data' => [
                        'code' => '200',
                        'message' => 'Sukses',
                    ],
                    'surat_kontrol' => [
                        'no_surat_kontrol' => $controlLetterNumber,
                        'jenis_kontrol' => '2',
                        'sep' => [
                            'no_sep' => '0301R0110725V000006',
                        ],
                        'form_prb' => [
                            'kode_status_prb' => null,
                            'data' => [],
                        ],
                    ],
                ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->getJson(route(
            'daftarOnline.suratKontrol.show',
            ['controlLetterNumber' => $controlLetterNumber]
        ));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Detail surat kontrol berhasil dimuat.')
            ->assertJsonPath(
                'data.surat_kontrol.no_surat_kontrol',
                $controlLetterNumber
            )
            ->assertJsonPath(
                'data.surat_kontrol.sep.no_sep',
                '0301R0110725V000006'
            );
    }

    public function test_control_letter_detail_returns_not_found_for_an_empty_vclaim_response(): void
    {
        $controlLetterNumber = '0301R0111125K000099';

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(RencanaKontrolService::class, function (MockInterface $mock) use ($controlLetterNumber): void {
            $mock
                ->shouldReceive('detailByControlLetterNumber')
                ->once()
                ->with($controlLetterNumber)
                ->andReturn([
                    'meta_data' => [
                        'code' => '201',
                        'message' => 'Data tidak ditemukan.',
                    ],
                    'surat_kontrol' => null,
                ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->getJson(route(
            'daftarOnline.suratKontrol.show',
            ['controlLetterNumber' => $controlLetterNumber]
        ));

        $response
            ->assertNotFound()
            ->assertJsonPath('status', 'not_found')
            ->assertJsonPath('message', 'Data tidak ditemukan.');
    }

    public function test_patient_without_configured_registration_role_cannot_query_control_letters(): void
    {
        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnFalse();
        });
        $this->mock(RencanaKontrolService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('listByCardNumber');
        });

        $user = new User([
            'name' => 'Pasien',
            'username' => '000123',
            'email' => 'pasien@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->getJson(route('daftarOnline.suratKontrol', [
            'tgl_registrasi' => now()->toDateString(),
            'no_peserta' => '0002035874204',
        ]));

        $response
            ->assertForbidden()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath(
                'message',
                'Anda tidak memiliki akses untuk mencari surat kontrol BPJS.'
            );
    }
}
