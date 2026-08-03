<?php

namespace Tests\Unit;

use App\Services\epasien\menu\FasilitasTarif\KamarService;
use App\Services\epasien\menu\FasilitasTarif\LaboratoriumService;
use App\Services\epasien\menu\FasilitasTarif\PoliklinikService;
use App\Services\epasien\menu\FasilitasTarif\RadiologiService;
use App\Services\epasien\menu\JadwalDokterService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class WarmKhanzaCacheCommandTest extends TestCase
{
    public function test_it_warms_the_default_reference_pages(): void
    {
        $jadwal = $this->createMock(JadwalDokterService::class);
        $jadwal->expects($this->once())->method('currentDay')->willReturn('SENIN');
        $jadwal
            ->expects($this->exactly(2))
            ->method('page')
            ->willReturn([]);

        $kamar = $this->createMock(KamarService::class);
        $kamar->expects($this->once())->method('rooms');
        $kamar->expects($this->once())->method('counts');
        $kamar->expects($this->once())->method('classes');

        $laboratorium = $this->createMock(LaboratoriumService::class);
        $laboratorium->expects($this->once())->method('items');
        $laboratorium->expects($this->once())->method('summary');
        $laboratorium->expects($this->once())->method('groups');

        $poliklinik = $this->createMock(PoliklinikService::class);
        $poliklinik->expects($this->once())->method('clinics');
        $poliklinik->expects($this->once())->method('summary');

        $radiologi = $this->createMock(RadiologiService::class);
        $radiologi->expects($this->once())->method('rates');
        $radiologi->expects($this->once())->method('summary');
        $radiologi->expects($this->once())->method('classes');

        $this->app->instance(JadwalDokterService::class, $jadwal);
        $this->app->instance(KamarService::class, $kamar);
        $this->app->instance(LaboratoriumService::class, $laboratorium);
        $this->app->instance(PoliklinikService::class, $poliklinik);
        $this->app->instance(RadiologiService::class, $radiologi);

        $this->assertSame(0, Artisan::call('epasien:warm-khanza-cache'));
    }
}
