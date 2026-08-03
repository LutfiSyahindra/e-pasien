<?php

namespace App\Console\Commands;

use App\Services\epasien\menu\FasilitasTarif\KamarService;
use App\Services\epasien\menu\FasilitasTarif\LaboratoriumService;
use App\Services\epasien\menu\FasilitasTarif\PoliklinikService;
use App\Services\epasien\menu\FasilitasTarif\RadiologiService;
use App\Services\epasien\menu\JadwalDokterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class WarmKhanzaCache extends Command
{
    protected $signature = 'epasien:warm-khanza-cache';

    protected $description = 'Warm frequently accessed E-Pasien reference data from Khanza';

    public function handle(
        JadwalDokterService $jadwalDokterService,
        KamarService $kamarService,
        LaboratoriumService $laboratoriumService,
        PoliklinikService $poliklinikService,
        RadiologiService $radiologiService
    ): int {
        $warmers = [
            'jadwal dokter' => function () use ($jadwalDokterService): void {
                $jadwalDokterService->page(
                    null,
                    $jadwalDokterService->currentDay(),
                    null
                );
                $jadwalDokterService->page(null, 'SEMUA', null);
            },
            'informasi kamar' => function () use ($kamarService): void {
                $kamarService->rooms();
                $kamarService->counts();
                $kamarService->classes();
            },
            'tarif laboratorium' => function () use ($laboratoriumService): void {
                $laboratoriumService->items();
                $laboratoriumService->summary();
                $laboratoriumService->groups();
            },
            'tarif poliklinik' => function () use ($poliklinikService): void {
                $poliklinikService->clinics();
                $poliklinikService->summary();
            },
            'tarif radiologi' => function () use ($radiologiService): void {
                $radiologiService->rates();
                $radiologiService->summary();
                $radiologiService->classes();
            },
        ];

        $failures = 0;

        foreach ($warmers as $label => $warm) {
            try {
                $warm();
                $this->components->info(ucfirst($label).' siap.');
            } catch (Throwable $exception) {
                $failures++;
                $this->components->warn(ucfirst($label).' belum dapat dipanaskan.');

                Log::warning('Pemanasan cache Khanza dilewati.', [
                    'dataset' => $label,
                    'exception' => $exception::class,
                ]);
            }
        }

        if ($failures > 0) {
            $this->components->warn(
                $failures.' dataset akan dimuat saat pertama kali dibutuhkan.'
            );
        }

        return self::SUCCESS;
    }
}
