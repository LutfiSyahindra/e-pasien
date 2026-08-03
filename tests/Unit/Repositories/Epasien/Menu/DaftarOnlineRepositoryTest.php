<?php

namespace Tests\Unit\Repositories\Epasien\Menu;

use App\Repositories\epasien\menu\DaftarOnlineRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DaftarOnlineRepositoryTest extends TestCase
{
    public function test_treatment_number_preview_skips_existing_mobile_jkn_booking_code(): void
    {
        $connection = Mockery::mock(Connection::class);
        $registrationQuery = Mockery::mock(Builder::class);
        $mobileJknQuery = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('reg_periksa')
            ->andReturn($registrationQuery);
        $registrationQuery->shouldReceive('where')
            ->once()
            ->with('tgl_registrasi', '2026-08-15')
            ->andReturnSelf();
        $registrationQuery->shouldReceive('orderByRaw')
            ->once()
            ->with("CAST(SUBSTRING_INDEX(no_rawat, '/', -1) AS UNSIGNED) DESC")
            ->andReturnSelf();
        $registrationQuery->shouldReceive('value')
            ->once()
            ->with('no_rawat')
            ->andReturn('2026/08/15/000028');
        $connection->shouldReceive('table')
            ->once()
            ->with('referensi_mobilejkn_bpjs')
            ->andReturn($mobileJknQuery);
        $mobileJknQuery->shouldReceive('where')
            ->once()
            ->with('nobooking', 'like', '20260815%')
            ->andReturnSelf();
        $mobileJknQuery->shouldReceive('orderByRaw')
            ->once()
            ->with('CAST(SUBSTRING(nobooking, 9) AS UNSIGNED) DESC')
            ->andReturnSelf();
        $mobileJknQuery->shouldReceive('value')
            ->once()
            ->with('nobooking')
            ->andReturn('20260815000029');

        $result = (new DaftarOnlineRepository)->previewNextTreatmentNumber('2026-08-15');

        $this->assertSame('2026/08/15/000030', $result);
    }

    public function test_registration_history_applies_date_range(): void
    {
        $connection = Mockery::mock(Connection::class);
        $query = Mockery::mock(Builder::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('reg_periksa')
            ->andReturn($query);
        $query->shouldReceive('leftJoin')->times(4)->andReturnSelf();
        $query->shouldReceive('select')->once()->andReturnSelf();
        $query->shouldReceive('when')
            ->once()
            ->andReturnUsing(function (bool $condition, \Closure $callback) use ($query): Builder {
                if ($condition) {
                    $callback($query);
                }

                return $query;
            });
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.no_rkm_medis', '000123')
            ->andReturnSelf();
        $query->shouldReceive('whereBetween')
            ->once()
            ->with('reg_periksa.tgl_registrasi', ['2026-07-01', '2026-07-31'])
            ->andReturnSelf();
        $query->shouldReceive('orderByDesc')
            ->once()
            ->with('reg_periksa.tgl_registrasi')
            ->andReturnSelf();
        $query->shouldReceive('orderByDesc')
            ->once()
            ->with('reg_periksa.jam_reg')
            ->andReturnSelf();
        $query->shouldReceive('paginate')->once()->with(8)->andReturn($paginator);
        $paginator->shouldReceive('withQueryString')->once()->andReturnSelf();

        $result = (new DaftarOnlineRepository)->paginateRegistrationHistory(
            '000123',
            '',
            8,
            '',
            '2026-07-01',
            '2026-07-31'
        );

        $this->assertSame($paginator, $result);
    }

    public function test_pending_registration_ignores_non_clinical_active_visit_clinics(): void
    {
        $registration = (object) [
            'no_reg' => '001',
            'kd_poli' => 'POL01',
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
        $query->shouldReceive('leftJoin')->times(4)->andReturnSelf();
        $query->shouldReceive('select')->once()->andReturnSelf();
        $query->shouldReceive('when')
            ->once()
            ->andReturnUsing(function (bool $condition, \Closure $callback) use ($query): Builder {
                if ($condition) {
                    $callback($query);
                }

                return $query;
            });
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.no_rkm_medis', '000123')
            ->andReturnSelf();
        $query->shouldReceive('selectRaw')->once()->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('reg_periksa.stts', 'Belum')
            ->andReturnSelf();
        $query->shouldReceive('whereRaw')
            ->once()
            ->with('UPPER(TRIM(reg_periksa.kd_poli)) NOT LIKE ?', ['IGD%'])
            ->andReturnSelf();
        $query->shouldReceive('whereRaw')
            ->once()
            ->with(
                "UPPER(TRIM(COALESCE(poliklinik.nm_poli, ''))) NOT IN (?, ?, ?, ?)",
                ['APOTEK', 'MCU', 'RADIOLOGI', 'UMUM']
            )
            ->andReturnSelf();
        $query->shouldReceive('whereRaw')
            ->once()
            ->with(
                "UPPER(TRIM(COALESCE(poliklinik.nm_poli, ''))) NOT LIKE ?",
                ['%LABORAT%']
            )
            ->andReturnSelf();
        $query->shouldReceive('orderByDesc')
            ->once()
            ->with('reg_periksa.tgl_registrasi')
            ->andReturnSelf();
        $query->shouldReceive('orderByDesc')
            ->once()
            ->with('reg_periksa.jam_reg')
            ->andReturnSelf();
        $query->shouldReceive('first')
            ->once()
            ->andReturn($registration);

        $result = (new DaftarOnlineRepository)->findPendingRegistration('000123');

        $this->assertSame($registration, $result);
    }

    public function test_pending_mobile_jkn_reference_is_scoped_to_patient_and_treatment(): void
    {
        $reference = (object) [
            'nobooking' => '20260728000001',
            'statuskirim' => 'Sudah',
            'sudah_checkin' => 0,
        ];
        $connection = Mockery::mock(Connection::class);
        $query = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('referensi_mobilejkn_bpjs as mobile_jkn')
            ->andReturn($query);
        $query->shouldReceive('join')->once()->andReturnSelf();
        $query->shouldReceive('select')->once()->andReturnSelf();
        $query->shouldReceive('selectRaw')->once()->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('registration.no_rawat', '2026/07/28/000001')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('registration.no_rkm_medis', '000123')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('registration.stts', 'Belum')
            ->andReturnSelf();
        $query->shouldReceive('whereNotNull')
            ->once()
            ->with('mobile_jkn.nobooking')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('mobile_jkn.nobooking', '<>', '')
            ->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($reference);

        $result = (new DaftarOnlineRepository)->findPendingMobileJknReference(
            '2026/07/28/000001',
            '000123'
        );

        $this->assertSame($reference, $result);
    }
}
