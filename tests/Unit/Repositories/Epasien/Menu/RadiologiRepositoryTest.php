<?php

namespace Tests\Unit\Repositories\Epasien\Menu;

use App\Repositories\epasien\menu\FasilitasTarif\RadiologiRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class RadiologiRepositoryTest extends TestCase
{
    public function test_rates_only_include_active_a09_radiology_services(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 12);
        $connection = Mockery::mock(Connection::class);
        $query = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('jns_perawatan_radiologi')
            ->andReturn($query);
        $query->shouldReceive('where')
            ->once()
            ->with('status', '1')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('kd_pj', 'A09')
            ->andReturnSelf();
        $query->shouldReceive('select')
            ->once()
            ->with('kd_jenis_prw', 'nm_perawatan', 'kelas', 'total_byr')
            ->andReturnSelf();
        $query->shouldReceive('when')
            ->twice()
            ->andReturnSelf();
        $query->shouldReceive('orderByRaw')->once()->andReturnSelf();
        $query->shouldReceive('orderBy')
            ->once()
            ->with('nm_perawatan')
            ->andReturnSelf();
        $query->shouldReceive('orderBy')
            ->once()
            ->with('kd_jenis_prw')
            ->andReturnSelf();
        $query->shouldReceive('paginate')
            ->once()
            ->with(12)
            ->andReturn($paginator);

        $result = (new RadiologiRepository)->paginateRates(
            null,
            null,
            12
        );

        $this->assertSame($paginator, $result);
    }
}
