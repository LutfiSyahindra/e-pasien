<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Repositories\epasien\menu\JadwalDokterRepository;
use App\Services\epasien\menu\JadwalDokterService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class JadwalDokterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_schedule_is_formatted_for_patient_display(): void
    {
        $repository = $this->createMock(JadwalDokterRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateSchedules')
            ->with('anak', 'SELASA', 'ANA', 12)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_dokter' => 'dryunus',
                    'nm_dokter' => 'dr. Achmad Yunus, Sp.A',
                    'jk' => 'L',
                    'kd_poli' => 'ANA',
                    'nm_poli' => 'Poliklinik Anak',
                    'hari_kerja' => 'SELASA',
                    'jam_mulai' => '12:00:00',
                    'jam_selesai' => '13:00:00',
                    'kuota' => 50,
                ],
            ], 1, 12));
        $repository
            ->expects($this->once())
            ->method('summary')
            ->willReturn((object) [
                'schedules' => 49,
                'doctors' => 18,
                'clinics' => 10,
            ]);
        $repository
            ->expects($this->once())
            ->method('clinics')
            ->willReturn(new Collection([
                (object) [
                    'kd_poli' => 'ANA',
                    'nm_poli' => 'Poliklinik Anak',
                ],
            ]));

        $page = (new JadwalDokterService($repository))->page(
            ' anak ',
            'SELASA',
            'ANA'
        );
        $schedule = $page['schedules']->items()[0];

        $this->assertSame('anak', $page['search']);
        $this->assertSame('Selasa', $page['day_label']);
        $this->assertSame('dr. Achmad Yunus, Sp.A', $schedule['doctor_name']);
        $this->assertSame('AY', $schedule['doctor_initials']);
        $this->assertSame('12.00 – 13.00 WIB', $schedule['time_label']);
        $this->assertSame('50 pasien', $schedule['quota_label']);
        $this->assertSame(18, $page['summary']['doctors']);
        $this->assertSame('Poliklinik Anak', $page['clinics'][0]['name']);
    }

    public function test_all_days_filter_does_not_restrict_repository_day(): void
    {
        $repository = $this->createMock(JadwalDokterRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateSchedules')
            ->with(null, null, null, 12)
            ->willReturn(new LengthAwarePaginator([], 0, 12));
        $repository
            ->expects($this->once())
            ->method('summary')
            ->willReturn(null);
        $repository
            ->expects($this->once())
            ->method('clinics')
            ->willReturn(new Collection);

        $page = (new JadwalDokterService($repository))->page(
            null,
            'SEMUA',
            null
        );

        $this->assertSame('SEMUA', $page['day']);
        $this->assertSame('Semua hari', $page['day_label']);
        $this->assertSame(0, $page['schedules']->total());
    }

    public function test_schedule_summary_and_clinics_are_cached(): void
    {
        $repository = $this->createMock(JadwalDokterRepository::class);
        $repository
            ->expects($this->exactly(2))
            ->method('paginateSchedules')
            ->willReturnCallback(
                fn () => new LengthAwarePaginator([], 0, 12)
            );
        $repository
            ->expects($this->once())
            ->method('summary')
            ->willReturn((object) [
                'schedules' => 2,
                'doctors' => 1,
                'clinics' => 1,
            ]);
        $repository
            ->expects($this->once())
            ->method('clinics')
            ->willReturn(collect([
                (object) [
                    'kd_poli' => 'ANA',
                    'nm_poli' => 'Poliklinik Anak',
                ],
            ]));

        $service = new JadwalDokterService($repository);
        $firstPage = $service->page(null, 'SEMUA', null);
        $secondPage = $service->page(null, 'SEMUA', null);

        $this->assertSame($firstPage['summary'], $secondPage['summary']);
        $this->assertSame($firstPage['clinics'], $secondPage['clinics']);
    }
}
