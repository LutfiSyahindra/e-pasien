<?php

namespace Tests\Unit\Repositories\Epasien\Menu;

use App\Repositories\epasien\menu\DaftarOnlineRepository;
use App\Repositories\epasien\settings\DoctorScheduleLeaveRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DaftarOnlineQuotaTest extends TestCase
{
    use RefreshDatabase;

    private Connection $connection;

    private array $locks = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql_khanza' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('mysql_khanza');
        $this->connection = DB::connection('mysql_khanza');
        $pdo = $this->connection->getPdo();
        // SQLite exercises the queries and transaction; MySQL advisory locks are simulated.
        $pdo->sqliteCreateFunction('GET_LOCK', function (string $name): int {
            $this->locks[] = 'acquire';

            return 1;
        }, 2);
        $pdo->sqliteCreateFunction('RELEASE_LOCK', function (string $name): int {
            $this->locks[] = 'release';

            return 1;
        }, 1);
        $pdo->sqliteCreateFunction('SUBSTRING_INDEX', fn (string $value, string $delimiter, int $count): string => implode($delimiter, array_slice(explode($delimiter, $value), $count)), 3);
        $schema = $this->connection->getSchemaBuilder();
        $schema->create('jadwal_spesialis', function (Blueprint $table): void {
            $table->string('kd_dokter');
            $table->string('kd_poli');
            $table->string('hari_kerja');
            $table->string('jam_mulai');
            $table->integer('kuota');
        });
        $schema->create('reg_periksa', function (Blueprint $table): void {
            foreach (['no_reg', 'no_rawat', 'tgl_registrasi', 'kd_dokter', 'kd_poli', 'no_rkm_medis', 'stts'] as $column) {
                $table->string($column);
            }
        });
        $schema->create('pasien', function (Blueprint $table): void {
            $table->string('no_rkm_medis');
            $table->string('no_peserta');
        });
        $schema->create('referensi_mobilejkn_bpjs', function (Blueprint $table): void {
            $table->string('nobooking');
            $table->string('no_rawat');
        });
        $this->connection->table('pasien')->insert(['no_rkm_medis' => '000123', 'no_peserta' => 'OLD']);
        $this->connection->table('jadwal_spesialis')->insert([
            'kd_dokter' => 'D001', 'kd_poli' => 'POL01', 'hari_kerja' => 'SENIN',
            'jam_mulai' => '08:00:00', 'kuota' => 1,
        ]);
    }

    public static function fullQuotas(): array
    {
        return [
            'regular full' => [false, 1, 1],
            'regular overbooked' => [false, 1, 2],
            'regular closed' => [false, 0, 0],
            'mjkn full' => [true, 1, 1],
            'mjkn overbooked' => [true, 1, 2],
            'mjkn closed' => [true, 0, 0],
        ];
    }

    #[DataProvider('fullQuotas')]
    public function test_full_quota_rejects_insert_and_card_update(bool $mjkn, int $quota, int $registered): void
    {
        $this->connection->table('jadwal_spesialis')->update(['kuota' => $quota]);
        for ($number = 1; $number <= $registered; $number++) {
            $this->connection->table('reg_periksa')->insert(array_merge($this->registration(), [
                'no_rkm_medis' => 'OTHER'.$number, 'no_reg' => (string) $number,
                'no_rawat' => '2026/07/27/'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
            ]));
        }

        try {
            $this->save($mjkn);
            $this->fail('A full schedule must reject registration.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('kuota', $exception->errors());
        }

        $this->assertSame($registered, $this->connection->table('reg_periksa')->count());
        $this->assertSame('OLD', $this->connection->table('pasien')->value('no_peserta'));
        $this->assertSame(0, $this->connection->table('referensi_mobilejkn_bpjs')->count());
        $this->assertSame(['acquire', 'release'], $this->locks);
    }

    public static function registrationFlows(): array
    {
        return ['regular' => [false], 'mjkn' => [true]];
    }

    #[DataProvider('registrationFlows')]
    public function test_cancelled_visits_free_a_slot_and_second_patient_cannot_exceed_quota(bool $mjkn): void
    {
        $this->connection->table('reg_periksa')->insert(array_merge($this->registration(), [
            'no_reg' => '001', 'no_rawat' => '2026/07/27/000001', 'no_rkm_medis' => 'CANCELLED', 'stts' => 'Batal',
        ]));

        $row = $this->save($mjkn);
        $this->assertSame('002', $row['no_reg']);
        $this->assertSame('NEW', $this->connection->table('pasien')->value('no_peserta'));
        $this->assertSame(1, (new DaftarOnlineRepository)->countActiveRegistrations('2026-07-27', 'D001', 'POL01'));

        try {
            $this->save($mjkn, '000456');
            $this->fail('The last slot has already been taken.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('kuota', $exception->errors());
        }

        $this->assertSame(2, $this->connection->table('reg_periksa')->count());
        $this->assertSame($mjkn ? 1 : 0, $this->connection->table('referensi_mobilejkn_bpjs')->count());
        $this->assertSame(['acquire', 'release', 'acquire', 'release'], $this->locks);
    }

    public function test_other_doctor_clinic_and_date_do_not_consume_selected_quota(): void
    {
        foreach ([['kd_dokter' => 'D002'], ['kd_poli' => 'POL02'], ['tgl_registrasi' => '2026-07-28']] as $index => $attributes) {
            $this->connection->table('reg_periksa')->insert(array_merge($this->registration(), [
                'no_reg' => '001', 'no_rawat' => '2026/07/27/00000'.($index + 1), 'no_rkm_medis' => 'OTHER'.$index,
            ], $attributes));
        }

        $this->assertNotNull($this->save(false));
        $this->assertSame(1, (new DaftarOnlineRepository)->countActiveRegistrations('2026-07-27', 'D001', 'POL01'));
    }

    #[DataProvider('registrationFlows')]
    public function test_registration_uses_managed_quota_even_when_legacy_schedule_is_closed(bool $mjkn): void
    {
        $this->connection->statement('CREATE TABLE jadwal AS SELECT * FROM jadwal_spesialis');
        $this->connection->table('jadwal')->update(['kuota' => 0]);
        $this->connection->table('reg_periksa')->insert(array_merge($this->registration(), [
            'no_reg' => '001', 'no_rawat' => '2026/07/27/000001', 'stts' => 'Batal',
        ]));

        $this->assertNotNull($this->save($mjkn));
        $this->assertSame(1, (new DaftarOnlineRepository)->countActiveRegistrations('2026-07-27', 'D001', 'POL01'));
    }

    private function registration(string $medicalRecord = '000123'): array
    {
        return [
            'tgl_registrasi' => '2026-07-27', 'kd_dokter' => 'D001', 'kd_poli' => 'POL01',
            'no_rkm_medis' => $medicalRecord, 'stts' => 'Belum',
        ];
    }

    public function test_leave_blocks_regular_and_mjkn_registration_even_when_quota_is_available(): void
    {
        app(DoctorScheduleLeaveRepository::class)->setOnLeave(
            $this->connection->table('jadwal_spesialis')->first(), true, '2026-07-27'
        );

        foreach ([false, true] as $mjkn) {
            try {
                $this->save($mjkn);
                $this->fail('A schedule on leave must reject registration.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('jadwal', $exception->errors());
            }
        }

        $this->assertSame(0, $this->connection->table('reg_periksa')->count());
        $this->assertSame('OLD', $this->connection->table('pasien')->value('no_peserta'));
        $this->assertSame(0, $this->connection->table('referensi_mobilejkn_bpjs')->count());
    }

    public function test_another_session_of_the_same_doctor_remains_open(): void
    {
        app(DoctorScheduleLeaveRepository::class)->setOnLeave(
            $this->connection->table('jadwal_spesialis')->first(), true, '2026-07-27'
        );
        $this->connection->table('jadwal_spesialis')->insert([
            'kd_dokter' => 'D001', 'kd_poli' => 'POL01', 'hari_kerja' => 'SENIN',
            'jam_mulai' => '13:00:00', 'kuota' => 1,
        ]);

        $this->assertNotNull($this->save(false));
        $this->assertSame(1, $this->connection->table('reg_periksa')->count());
    }

    #[DataProvider('registrationFlows')]
    public function test_leave_on_another_date_does_not_block_registration(bool $mjkn): void
    {
        app(DoctorScheduleLeaveRepository::class)->setOnLeave(
            $this->connection->table('jadwal_spesialis')->first(), true, '2026-08-03'
        );
        $this->connection->table('reg_periksa')->insert(array_merge($this->registration(), [
            'no_reg' => '001', 'no_rawat' => '2026/07/27/000001', 'stts' => 'Batal',
        ]));

        $this->assertNotNull($this->save($mjkn));
        $this->assertSame(1, (new DaftarOnlineRepository)->countActiveRegistrations('2026-07-27', 'D001', 'POL01'));
    }

    private function save(bool $mjkn, string $medicalRecord = '000123'): ?array
    {
        $repository = new DaftarOnlineRepository;

        return $mjkn
            ? $repository->createMjknRegistration($this->registration($medicalRecord), 'NEW', [
                'nobooking' => '20260727000002',
            ], '002', '2026/07/27/000002')
            : $repository->createRegistration($this->registration($medicalRecord), 'NEW');
    }
}
