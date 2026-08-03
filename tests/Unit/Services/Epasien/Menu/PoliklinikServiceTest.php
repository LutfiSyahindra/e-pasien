<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Repositories\epasien\menu\FasilitasTarif\PoliklinikRepository;
use App\Services\epasien\menu\FasilitasTarif\PoliklinikService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PoliklinikServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_clinic_fees_are_formatted_for_new_and_returning_patients(): void
    {
        $repository = $this->createMock(PoliklinikRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateClinics')
            ->with('anak', 12)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_poli' => 'ANA',
                    'nm_poli' => 'Poliklinik Anak',
                    'registrasi' => 5000,
                    'registrasilama' => 3000,
                ],
            ], 1, 12));

        $service = new PoliklinikService($repository);
        $clinics = $service->clinics(' anak ');
        $cachedClinics = $service->clinics(' anak ');
        $clinic = $clinics->items()[0];

        $this->assertSame('ANA', $clinic['code']);
        $this->assertSame('Poliklinik Anak', $clinic['name']);
        $this->assertSame('bi-emoji-smile', $clinic['icon']);
        $this->assertSame(5000.0, $clinic['new_fee']);
        $this->assertSame('Rp 5.000', $clinic['new_fee_formatted']);
        $this->assertTrue($clinic['new_fee_available']);
        $this->assertSame(3000.0, $clinic['returning_fee']);
        $this->assertSame('Rp 3.000', $clinic['returning_fee_formatted']);
        $this->assertTrue($clinic['returning_fee_available']);
        $this->assertSame($clinics->items(), $cachedClinics->items());
    }

    public function test_zero_fee_is_not_presented_as_free(): void
    {
        $repository = $this->createMock(PoliklinikRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateClinics')
            ->with(null, 12)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_poli' => 'U0043',
                    'nm_poli' => 'fasilitas kesehatan tingkat 1',
                    'registrasi' => 0,
                    'registrasilama' => 0,
                ],
            ], 1, 12));

        $clinic = (new PoliklinikService($repository))->clinics()->items()[0];

        $this->assertSame('Fasilitas Kesehatan Tingkat 1', $clinic['name']);
        $this->assertFalse($clinic['new_fee_available']);
        $this->assertFalse($clinic['returning_fee_available']);
        $this->assertSame('Konfirmasi tarif', $clinic['new_fee_formatted']);
        $this->assertSame('Konfirmasi tarif', $clinic['returning_fee_formatted']);
    }
}
