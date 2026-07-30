<?php

namespace Tests\Unit\Repositories\Epasien\Menu;

use App\Repositories\epasien\menu\ResepObatRepository;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use ReflectionMethod;
use Tests\TestCase;

class ResepObatRepositoryTest extends TestCase
{
    public function test_union_keeps_doctor_and_discharge_columns_aligned(): void
    {
        $connection = new MySqlConnection(null, 'khanza-test');
        $query = $this->prescriptionUnionQuery($connection, '000123');
        $dischargeQuery = $query->unions[0]['query'];
        $expectedAliases = [
            'nomor_resep',
            'no_rawat',
            'sumber',
            'tanggal',
            'jam',
            'status_layanan',
            'status_proses',
            'tanggal_selesai',
            'jam_selesai',
            'nm_dokter',
            'nm_poli',
            'jumlah_obat',
            'jumlah_racikan',
        ];

        $this->assertSame(
            $expectedAliases,
            $this->columnAliases($query, $connection)
        );
        $this->assertSame(
            $expectedAliases,
            $this->columnAliases($dischargeQuery, $connection)
        );
    }

    public function test_doctor_flow_is_ralan_only_and_both_flows_are_patient_scoped(): void
    {
        $connection = new MySqlConnection(null, 'khanza-test');
        $query = $this->prescriptionUnionQuery($connection, '000123');
        $dischargeQuery = $query->unions[0]['query'];

        $this->assertStringContainsString(
            '`resep_obat`',
            $query->toSql()
        );
        $this->assertStringContainsString(
            '`permintaan_resep_pulang` as `permintaan`',
            $dischargeQuery->toSql()
        );
        $this->assertStringContainsString(
            '`resep_obat`.`status` = ?',
            $query->toSql()
        );
        $this->assertSame(
            ['000123', 'ralan'],
            $query->getRawBindings()['where']
        );
        $this->assertSame(
            ['000123'],
            $dischargeQuery->getRawBindings()['where']
        );
    }

    private function prescriptionUnionQuery(
        MySqlConnection $connection,
        string $medicalRecordNumber
    ): Builder {
        $method = new ReflectionMethod(
            ResepObatRepository::class,
            'prescriptionUnionQuery'
        );

        return $method->invoke(
            new ResepObatRepository,
            $connection,
            $medicalRecordNumber
        );
    }

    /**
     * @return array<int, string>
     */
    private function columnAliases(
        Builder $query,
        MySqlConnection $connection
    ): array {
        return collect($query->columns)
            ->map(function (mixed $column) use ($connection): string {
                $sql = $column instanceof Expression
                    ? $column->getValue($connection->getQueryGrammar())
                    : (string) $column;

                if (preg_match_all(
                    '/\s+as\s+[`"]?([a-z_]+)[`"]?/i',
                    $sql,
                    $matches
                )) {
                    return strtolower((string) end($matches[1]));
                }

                return strtolower(
                    trim((string) preg_replace('/^.*\./', '', $sql), '`" ')
                );
            })
            ->all();
    }
}
