<?php

namespace Tests\Unit\Repositories\Epasien\Menu;

use App\Repositories\epasien\menu\FasilitasTarif\LaboratoriumRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class LaboratoriumRepositoryTest extends TestCase
{
    public function test_items_join_templates_to_active_a09_laboratory_services(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 16);
        $connection = Mockery::mock(Connection::class);
        $query = Mockery::mock(Builder::class);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_khanza')
            ->andReturn($connection);
        $connection->shouldReceive('table')
            ->once()
            ->with('template_laboratorium as template')
            ->andReturn($query);
        $query->shouldReceive('join')
            ->once()
            ->with(
                'jns_perawatan_lab as jenis',
                'jenis.kd_jenis_prw',
                '=',
                'template.kd_jenis_prw'
            )
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('jenis.status', '1')
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with('jenis.kd_pj', 'A09')
            ->andReturnSelf();
        $query->shouldReceive('select')
            ->once()
            ->with(
                'jenis.kd_jenis_prw',
                'jenis.nm_perawatan',
                'jenis.kelas',
                'jenis.kategori',
                'template.id_template',
                'template.Pemeriksaan as nama_pemeriksaan',
                'template.satuan',
                'template.biaya_item',
                'template.urut'
            )
            ->andReturnSelf();
        $query->shouldReceive('when')
            ->twice()
            ->andReturnSelf();
        $query->shouldReceive('orderBy')
            ->once()
            ->with('jenis.nm_perawatan')
            ->andReturnSelf();
        $query->shouldReceive('orderBy')
            ->once()
            ->with('template.urut')
            ->andReturnSelf();
        $query->shouldReceive('orderBy')
            ->once()
            ->with('template.Pemeriksaan')
            ->andReturnSelf();
        $query->shouldReceive('paginate')
            ->once()
            ->with(16)
            ->andReturn($paginator);

        $result = (new LaboratoriumRepository)->paginateItems(
            null,
            null,
            16
        );

        $this->assertSame($paginator, $result);
    }
}
