<?php

namespace Tests\Unit\Repositories\Epasien\Menu;

use App\Repositories\epasien\menu\FasilitasTarif\PoliklinikRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PoliklinikRepositoryTest extends TestCase
{
    public function test_clinics_only_include_active_specialist_polyclinics(): void
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
            ->with('poliklinik')
            ->andReturn($query);
        $query->shouldReceive('where')
            ->once()
            ->with('status', '1')
            ->andReturnSelf();
        $query->shouldReceive('whereNotNull')
            ->once()
            ->with('kd_poli')
            ->andReturnSelf();
        $query->shouldReceive('whereNotNull')
            ->once()
            ->with('nm_poli')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('kd_poli', '<>', '')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('nm_poli', '<>', '')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('kd_poli', '<>', '-')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('nm_poli', '<>', '-')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('nm_poli', 'like', 'Poliklinik %')
            ->andReturnSelf();
        $query->shouldReceive('select')
            ->once()
            ->with(
                'kd_poli',
                'nm_poli',
                'registrasi',
                'registrasilama'
            )
            ->andReturnSelf();
        $query->shouldReceive('when')
            ->once()
            ->andReturnSelf();
        $query->shouldReceive('orderBy')
            ->once()
            ->with('nm_poli')
            ->andReturnSelf();
        $query->shouldReceive('orderBy')
            ->once()
            ->with('kd_poli')
            ->andReturnSelf();
        $query->shouldReceive('paginate')
            ->once()
            ->with(12)
            ->andReturn($paginator);

        $result = (new PoliklinikRepository)->paginateClinics(null, 12);

        $this->assertSame($paginator, $result);
    }
}
