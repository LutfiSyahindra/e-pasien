<?php

namespace Tests\Unit\Repositories\Epasien\Menu;

use App\Repositories\epasien\menu\RiwayatPemeriksaanRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RiwayatPemeriksaanRepositoryTest extends TestCase
{
    public function test_history_query_is_scoped_to_completed_patient_visits_and_care_type(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 8);
        $connection = Mockery::mock(Connection::class);
        $query = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('reg_periksa')
            ->andReturn($query);
        $query->shouldReceive('leftJoin')->times(3)->andReturnSelf();
        $query->shouldReceive('select')->once()->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.no_rkm_medis', '000123')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.stts', 'Sudah')
            ->andReturnSelf();
        $query->shouldReceive('when')
            ->times(4)
            ->andReturnUsing(function (mixed $value, \Closure $callback) use ($query): Builder {
                if ($value) {
                    $callback($query);
                }

                return $query;
            });
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.status_lanjut', 'Ralan')
            ->andReturnSelf();
        $query->shouldReceive('whereDate')
            ->once()
            ->with('reg_periksa.tgl_registrasi', '>=', '2026-07-01')
            ->andReturnSelf();
        $query->shouldReceive('whereDate')
            ->once()
            ->with('reg_periksa.tgl_registrasi', '<=', '2026-07-31')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.kd_dokter', 'D001')
            ->andReturnSelf();
        $query->shouldReceive('orderByDesc')
            ->once()
            ->with('reg_periksa.tgl_registrasi')
            ->andReturnSelf();
        $query->shouldReceive('orderByDesc')
            ->once()
            ->with('reg_periksa.jam_reg')
            ->andReturnSelf();
        $query->shouldReceive('paginate')
            ->once()
            ->with(8)
            ->andReturn($paginator);

        $result = (new RiwayatPemeriksaanRepository)
            ->paginateCompletedExaminations(
                '000123',
                'Ralan',
                '2026-07-01',
                '2026-07-31',
                'D001',
                8
            );

        $this->assertSame($paginator, $result);
    }

    public function test_doctor_options_only_use_doctors_from_the_patients_completed_visits(): void
    {
        $doctors = new Collection([(object) [
            'kd_dokter' => 'D001',
            'nm_dokter' => 'dr. Sehat',
        ]]);
        $connection = Mockery::mock(Connection::class);
        $query = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('reg_periksa')
            ->andReturn($query);
        $query->shouldReceive('leftJoin')
            ->once()
            ->with('dokter', 'dokter.kd_dokter', '=', 'reg_periksa.kd_dokter')
            ->andReturnSelf();
        $query->shouldReceive('select')->once()->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.no_rkm_medis', '000123')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.stts', 'Sudah')
            ->andReturnSelf();
        $query->shouldReceive('whereNotNull')
            ->once()
            ->with('reg_periksa.kd_dokter')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.kd_dokter', '<>', '')
            ->andReturnSelf();
        $query->shouldReceive('distinct')->once()->andReturnSelf();
        $query->shouldReceive('orderBy')
            ->once()
            ->with('dokter.nm_dokter')
            ->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($doctors);

        $result = (new RiwayatPemeriksaanRepository)
            ->completedExaminationDoctors('000123');

        $this->assertSame($doctors, $result);
    }

    public function test_completed_counts_are_scoped_to_patient_and_completed_status(): void
    {
        $connection = Mockery::mock(Connection::class);
        $query = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('reg_periksa')
            ->andReturn($query);
        $query->shouldReceive('where')
            ->once()
            ->with('no_rkm_medis', '000123')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('stts', 'Sudah')
            ->andReturnSelf();
        $query->shouldReceive('selectRaw')
            ->once()
            ->with('COUNT(*) as total')
            ->andReturnSelf();
        $query->shouldReceive('selectRaw')
            ->once()
            ->with("SUM(CASE WHEN status_lanjut = 'Ralan' THEN 1 ELSE 0 END) as ralan")
            ->andReturnSelf();
        $query->shouldReceive('selectRaw')
            ->once()
            ->with("SUM(CASE WHEN status_lanjut = 'Ranap' THEN 1 ELSE 0 END) as ranap")
            ->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn((object) [
            'total' => 5,
            'ralan' => 3,
            'ranap' => 2,
        ]);

        $counts = (new RiwayatPemeriksaanRepository)
            ->completedExaminationCounts('000123');

        $this->assertSame([
            'all' => 5,
            'Ralan' => 3,
            'Ranap' => 2,
        ], $counts);
    }

    #[DataProvider('resumeTableProvider')]
    public function test_resume_query_uses_the_care_type_table_and_is_scoped_to_the_patient(
        string $careType,
        string $resumeTable
    ): void {
        $resume = (object) [
            'no_rawat' => '2026/07/20/000007',
            'nm_dokter' => 'dr. Sehat',
        ];
        $connection = Mockery::mock(Connection::class);
        $query = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('reg_periksa')
            ->andReturn($query);
        $query->shouldReceive('join')
            ->once()
            ->with(
                $resumeTable,
                $resumeTable.'.no_rawat',
                '=',
                'reg_periksa.no_rawat'
            )
            ->andReturnSelf();
        $query->shouldReceive('leftJoin')
            ->once()
            ->with('dokter', 'dokter.kd_dokter', '=', $resumeTable.'.kd_dokter')
            ->andReturnSelf();
        $query->shouldReceive('select')
            ->once()
            ->with($resumeTable.'.*', 'dokter.nm_dokter')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.no_rkm_medis', '000123')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.no_rawat', '2026/07/20/000007')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.status_lanjut', $careType)
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.stts', 'Sudah')
            ->andReturnSelf();
        $query->shouldReceive('first')
            ->once()
            ->andReturn($resume);

        $result = (new RiwayatPemeriksaanRepository)->findResumeForCompletedVisit(
            '000123',
            '2026/07/20/000007',
            $careType
        );

        $this->assertSame($resume, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function resumeTableProvider(): array
    {
        return [
            'rawat jalan' => ['Ralan', 'resume_pasien'],
            'rawat inap' => ['Ranap', 'resume_pasien_ranap'],
        ];
    }

    public function test_billing_query_loads_all_rows_in_noindex_order_after_patient_ownership_check(): void
    {
        $visit = (object) [
            'no_rawat' => '2026/07/20/000007',
            'no_rkm_medis' => '000123',
        ];
        $billingRows = new Collection([(object) [
            'noindex' => 0,
            'no_rawat' => '2026/07/20/000007',
        ]]);
        $connection = Mockery::mock(Connection::class);
        $visitQuery = Mockery::mock(Builder::class);
        $billingQuery = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('reg_periksa')
            ->andReturn($visitQuery);
        $visitQuery->shouldReceive('leftJoin')->times(4)->andReturnSelf();
        $visitQuery->shouldReceive('select')->once()->andReturnSelf();
        $visitQuery->shouldReceive('where')
            ->once()
            ->with('reg_periksa.no_rkm_medis', '000123')
            ->andReturnSelf();
        $visitQuery->shouldReceive('where')
            ->once()
            ->with('reg_periksa.no_rawat', '2026/07/20/000007')
            ->andReturnSelf();
        $visitQuery->shouldReceive('where')
            ->once()
            ->with('reg_periksa.stts', 'Sudah')
            ->andReturnSelf();
        $visitQuery->shouldReceive('first')->once()->andReturn($visit);

        $connection->shouldReceive('table')
            ->once()
            ->with('billing')
            ->andReturn($billingQuery);
        $billingQuery->shouldReceive('select')
            ->once()
            ->with('billing.*')
            ->andReturnSelf();
        $billingQuery->shouldReceive('where')
            ->once()
            ->with('billing.no_rawat', '2026/07/20/000007')
            ->andReturnSelf();
        $billingQuery->shouldReceive('orderBy')
            ->once()
            ->with('billing.noindex')
            ->andReturnSelf();
        $billingQuery->shouldReceive('get')->once()->andReturn($billingRows);

        $result = (new RiwayatPemeriksaanRepository)->findBillingForCompletedVisit(
            '000123',
            '2026/07/20/000007'
        );

        $this->assertSame($visit, $result['visit']);
        $this->assertSame($billingRows, $result['rows']);
    }
}
