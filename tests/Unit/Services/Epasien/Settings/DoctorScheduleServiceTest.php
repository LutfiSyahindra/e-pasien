<?php

namespace Tests\Unit\Services\Epasien\Settings;

use App\Repositories\epasien\settings\DoctorScheduleRepository;
use App\Services\epasien\settings\DoctorScheduleService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DoctorScheduleServiceTest extends TestCase
{
    public function test_sunday_uses_minggu_in_epasien_and_akhad_in_database(): void
    {
        $repository = $this->createMock(DoctorScheduleRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginate')
            ->with(null, 'AKHAD', null, 15)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_dokter' => 'drminggu',
                    'nm_dokter' => 'dr. Dokter Minggu',
                    'kd_poli' => 'INT',
                    'nm_poli' => 'Poliklinik Penyakit Dalam',
                    'hari_kerja' => 'Akhad',
                    'jam_mulai' => '08:00:00',
                    'jam_selesai' => '10:00:00',
                    'kuota' => 20,
                ],
            ], 1, 15));
        $repository
            ->expects($this->once())
            ->method('summary')
            ->willReturn(null);
        $repository
            ->expects($this->once())
            ->method('clinics')
            ->willReturn(new Collection);

        $page = (new DoctorScheduleService($repository))->page(
            null,
            'AKHAD',
            null
        );
        $schedule = $page['schedules']->items()[0];

        $this->assertSame('MINGGU', $page['day']);
        $this->assertArrayHasKey('MINGGU', $page['days']);
        $this->assertArrayNotHasKey('AKHAD', $page['days']);
        $this->assertSame('MINGGU', $schedule['day']);
        $this->assertSame('Minggu', $schedule['day_label']);
    }

    public function test_update_translates_minggu_to_akhad_for_database(): void
    {
        $repository = $this->createMock(DoctorScheduleRepository::class);
        $repository
            ->expects($this->once())
            ->method('update')
            ->with(
                [
                    'doctor_code' => 'drminggu',
                    'day' => 'AKHAD',
                    'start_time' => '08:00',
                ],
                [
                    'day' => 'AKHAD',
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'quota' => 25,
                ]
            );

        (new DoctorScheduleService($repository))->update(
            [
                'doctor_code' => 'drminggu',
                'day' => 'MINGGU',
                'start_time' => '08:00',
            ],
            [
                'day' => 'MINGGU',
                'start_time' => '09:00',
                'end_time' => '11:00',
                'quota' => 25,
            ]
        );
    }
}
