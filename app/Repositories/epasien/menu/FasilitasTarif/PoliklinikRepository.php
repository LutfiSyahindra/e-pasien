<?php

namespace App\Repositories\epasien\menu\FasilitasTarif;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PoliklinikRepository
{
    private const ACTIVE_STATUS = '1';

    private const PLACEHOLDER_VALUE = '-';

    public function paginateClinics(
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        return $this->activeClinicQuery()
            ->select(
                'kd_poli',
                'nm_poli',
                'registrasi',
                'registrasilama'
            )
            ->when(
                $search !== null,
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('nm_poli', 'like', '%'.$search.'%')
                            ->orWhere('kd_poli', 'like', '%'.$search.'%');
                    });
                }
            )
            ->orderBy('nm_poli')
            ->orderBy('kd_poli')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return object{
     *     total: int|string|null,
     *     priced: int|string|null,
     *     minimum: int|float|string|null,
     *     maximum: int|float|string|null
     * }|null
     */
    public function summary(): ?object
    {
        return $this->activeClinicQuery()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN registrasi > 0 OR registrasilama > 0 '
                .'THEN 1 ELSE 0 END) as priced'
            )
            ->selectRaw(
                'MIN(CASE '
                .'WHEN registrasi > 0 AND registrasilama > 0 '
                .'THEN LEAST(registrasi, registrasilama) '
                .'WHEN registrasi > 0 THEN registrasi '
                .'WHEN registrasilama > 0 THEN registrasilama '
                .'END) as minimum'
            )
            ->selectRaw(
                'GREATEST('
                .'COALESCE(MAX(registrasi), 0), '
                .'COALESCE(MAX(registrasilama), 0)'
                .') as maximum'
            )
            ->first();
    }

    private function activeClinicQuery(): Builder
    {
        return $this->connection()
            ->table('poliklinik')
            ->where('status', self::ACTIVE_STATUS)
            ->whereNotNull('kd_poli')
            ->whereNotNull('nm_poli')
            ->where('kd_poli', '<>', '')
            ->where('nm_poli', '<>', '')
            ->where('kd_poli', '<>', self::PLACEHOLDER_VALUE)
            ->where('nm_poli', '<>', self::PLACEHOLDER_VALUE)
            ->where('nm_poli', 'like', 'Poliklinik %');
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
