<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Repositories\epasien\menu\FasilitasTarif\LaboratoriumRepository;
use App\Services\epasien\menu\FasilitasTarif\LaboratoriumService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class LaboratoriumServiceTest extends TestCase
{
    public function test_items_use_template_price_and_are_formatted_for_patients(): void
    {
        $repository = $this->createMock(LaboratoriumRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateItems')
            ->with('J000111', 'hemoglobin', 16)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_jenis_prw' => 'J000111',
                    'id_template' => 3273,
                    'nm_perawatan' => 'DARAH LENGKAP',
                    'nama_pemeriksaan' => 'Hemoglobin',
                    'satuan' => 'g/dl',
                    'biaya_item' => 15000,
                ],
            ], 1, 16));

        $items = (new LaboratoriumService($repository))->items(
            ' J000111 ',
            ' hemoglobin '
        );
        $item = $items->items()[0];

        $this->assertSame('J000111', $item['code']);
        $this->assertSame('3273', $item['template_id']);
        $this->assertSame('DARAH LENGKAP', $item['group_name']);
        $this->assertSame('Hemoglobin', $item['name']);
        $this->assertSame('g/dl', $item['unit']);
        $this->assertTrue($item['has_unit']);
        $this->assertSame(15000.0, $item['tariff']);
        $this->assertTrue($item['has_tariff']);
        $this->assertSame('Rp 15.000', $item['tariff_formatted']);
    }

    public function test_zero_template_price_asks_patient_to_confirm(): void
    {
        $repository = $this->createMock(LaboratoriumRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateItems')
            ->with(null, null, 16)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_jenis_prw' => 'J000157',
                    'id_template' => 3300,
                    'nm_perawatan' => 'AFP',
                    'nama_pemeriksaan' => 'AFP',
                    'satuan' => '',
                    'biaya_item' => 0,
                ],
            ], 1, 16));

        $item = (new LaboratoriumService($repository))->items()->items()[0];

        $this->assertFalse($item['has_tariff']);
        $this->assertFalse($item['has_unit']);
        $this->assertSame('Konfirmasi tarif', $item['tariff_formatted']);
    }
}
