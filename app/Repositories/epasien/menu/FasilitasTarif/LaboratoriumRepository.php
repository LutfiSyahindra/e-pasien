<?php

namespace App\Repositories\epasien\menu\FasilitasTarif;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LaboratoriumRepository
{
    private const ACTIVE_STATUS = '1';

    private const PAYOR_CODE = 'A09';

    public function paginateItems(
        ?string $group,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        return $this->activeItemQuery()
            ->select(
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
            ->when(
                $group !== null,
                fn (Builder $query) => $query->where(
                    'jenis.kd_jenis_prw',
                    $group
                )
            )
            ->when(
                $search !== null,
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where(
                                'template.Pemeriksaan',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'jenis.nm_perawatan',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'jenis.kd_jenis_prw',
                                'like',
                                '%'.$search.'%'
                            );
                    });
                }
            )
            ->orderBy('jenis.nm_perawatan')
            ->orderBy('template.urut')
            ->orderBy('template.Pemeriksaan')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, object>
     */
    public function groups(): Collection
    {
        return $this->activeItemQuery()
            ->select(
                'jenis.kd_jenis_prw',
                'jenis.nm_perawatan'
            )
            ->selectRaw('COUNT(template.id_template) as total_items')
            ->groupBy(
                'jenis.kd_jenis_prw',
                'jenis.nm_perawatan'
            )
            ->orderBy('jenis.nm_perawatan')
            ->get();
    }

    /**
     * @return object{
     *     groups_count: int|string|null,
     *     items_count: int|string|null,
     *     priced_count: int|string|null,
     *     minimum: int|float|string|null,
     *     maximum: int|float|string|null
     * }|null
     */
    public function summary(): ?object
    {
        return $this->activeItemQuery()
            ->selectRaw(
                'COUNT(DISTINCT jenis.kd_jenis_prw) as groups_count'
            )
            ->selectRaw('COUNT(template.id_template) as items_count')
            ->selectRaw(
                'SUM(CASE WHEN template.biaya_item > 0 THEN 1 ELSE 0 END) '
                .'as priced_count'
            )
            ->selectRaw(
                'MIN(CASE WHEN template.biaya_item > 0 '
                .'THEN template.biaya_item END) as minimum'
            )
            ->selectRaw('MAX(template.biaya_item) as maximum')
            ->first();
    }

    private function activeItemQuery(): Builder
    {
        return $this->connection()
            ->table('template_laboratorium as template')
            ->join(
                'jns_perawatan_lab as jenis',
                'jenis.kd_jenis_prw',
                '=',
                'template.kd_jenis_prw'
            )
            ->where('jenis.status', self::ACTIVE_STATUS)
            ->where('jenis.kd_pj', self::PAYOR_CODE);
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
