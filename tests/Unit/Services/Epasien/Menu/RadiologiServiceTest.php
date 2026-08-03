<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Repositories\epasien\menu\FasilitasTarif\RadiologiRepository;
use App\Services\epasien\menu\FasilitasTarif\RadiologiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RadiologiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_rates_are_trimmed_and_formatted_for_patients(): void
    {
        $repository = $this->createMock(RadiologiRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateRates')
            ->with('Rawat Jalan', 'thorax', 12)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_jenis_prw' => 'RAD001',
                    'nm_perawatan' => 'Foto Thorax PA',
                    'kelas' => 'Rawat Jalan',
                    'total_byr' => 175000,
                ],
            ], 1, 12));

        $service = new RadiologiService($repository);
        $rates = $service->rates(
            ' Rawat Jalan ',
            ' thorax '
        );
        $cachedRates = $service->rates(' Rawat Jalan ', ' thorax ');
        $rate = $rates->items()[0];

        $this->assertSame('RAD001', $rate['code']);
        $this->assertSame('Foto Thorax PA', $rate['name']);
        $this->assertSame('Rawat Jalan', $rate['class_label']);
        $this->assertSame(175000.0, $rate['tariff']);
        $this->assertSame('Rp 175.000', $rate['tariff_formatted']);
        $this->assertSame($rates->items(), $cachedRates->items());
    }

    public function test_dash_class_has_a_patient_friendly_label(): void
    {
        $repository = $this->createMock(RadiologiRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateRates')
            ->with(null, null, 12)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_jenis_prw' => 'RAD002',
                    'nm_perawatan' => 'USG',
                    'kelas' => '-',
                    'total_byr' => 250000,
                ],
            ], 1, 12));

        $rate = (new RadiologiService($repository))->rates()->items()[0];

        $this->assertSame('Tanpa kelas', $rate['class_label']);
        $this->assertSame('Rp 250.000', $rate['tariff_formatted']);
    }
}
