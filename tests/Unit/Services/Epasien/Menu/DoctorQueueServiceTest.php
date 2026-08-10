<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Repositories\epasien\menu\DoctorArrivalRepository;
use App\Services\epasien\menu\DoctorQueueService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DoctorQueueServiceTest extends TestCase
{
    public function test_current_formats_latest_calls_and_marks_the_patients_queue(): void
    {
        $serviceDate = '2026-09-14';
        Cache::forget('epasien:khanza:doctor-queues:v2:'.$serviceDate);

        $repository = $this->createMock(DoctorArrivalRepository::class);
        $repository
            ->expects($this->once())
            ->method('currentQueues')
            ->with($serviceDate)
            ->willReturn(collect([
                (object) [
                    'queue_number' => '009',
                    'last_serviced_number' => '008',
                    'queue_state' => 'calling',
                    'doctor_code' => 'D001',
                    'doctor_name' => 'dr. Sehat, Sp.A',
                    'clinic_code' => 'ANA',
                    'clinic_name' => 'Poliklinik Anak',
                    'serviced_at' => '2026-09-14 09:15:00',
                ],
                (object) [
                    'queue_number' => '008',
                    'last_serviced_number' => '007',
                    'queue_state' => 'calling',
                    'doctor_code' => 'D001',
                    'doctor_name' => 'dr. Sehat, Sp.A',
                    'clinic_code' => 'ANA',
                    'clinic_name' => 'Poliklinik Anak',
                    'serviced_at' => '2026-09-14 09:10:00',
                ],
                (object) [
                    'queue_number' => '003',
                    'last_serviced_number' => '002',
                    'queue_state' => 'calling',
                    'doctor_code' => 'D002',
                    'doctor_name' => 'dr. Budi, Sp.PD',
                    'clinic_code' => 'INT',
                    'clinic_name' => 'Poliklinik Penyakit Dalam',
                    'serviced_at' => '2026-09-14 09:12:00',
                ],
            ]));

        $registration = [
            'tanggal' => $serviceDate,
            'kd_dokter' => 'D001',
            'kd_poli' => 'ANA',
            'no_reg' => '009',
        ];

        $queues = (new DoctorQueueService($repository))->current(
            $registration,
            Carbon::parse($serviceDate.' 09:16:00', 'Asia/Jakarta'),
        );

        $this->assertCount(2, $queues);
        $patientQueue = $queues->firstWhere('doctor_code', 'D001');
        $this->assertSame('009', $patientQueue['current_number']);
        $this->assertSame('008', $patientQueue['last_serviced_number']);
        $this->assertSame('Sedang dipanggil', $patientQueue['number_label']);
        $this->assertTrue($patientQueue['is_patient_queue']);
        $this->assertSame('009', $patientQueue['patient_number']);
        $this->assertSame(0, $patientQueue['remaining_before_patient']);
        $this->assertSame(
            'Antrean Anda sedang dipanggil. Silakan menuju poli sekarang.',
            $patientQueue['patient_message']
        );
        $this->assertSame('09:15 WIB', $patientQueue['serviced_at_label']);
    }

    public function test_current_does_not_mark_a_registration_from_another_date(): void
    {
        $serviceDate = '2026-09-15';
        Cache::forget('epasien:khanza:doctor-queues:v2:'.$serviceDate);

        $repository = $this->createMock(DoctorArrivalRepository::class);
        $repository
            ->expects($this->once())
            ->method('currentQueues')
            ->with($serviceDate)
            ->willReturn(collect([(object) [
                'queue_number' => '007',
                'last_serviced_number' => '007',
                'queue_state' => 'waiting',
                'doctor_code' => 'D001',
                'doctor_name' => 'dr. Sehat, Sp.A',
                'clinic_code' => 'ANA',
                'clinic_name' => 'Poliklinik Anak',
                'serviced_at' => null,
            ]]));

        $queue = (new DoctorQueueService($repository))->current(
            [
                'tanggal' => '2026-09-16',
                'kd_dokter' => 'D001',
                'kd_poli' => 'ANA',
                'no_reg' => '009',
            ],
            Carbon::parse($serviceDate.' 09:16:00', 'Asia/Jakarta'),
        )->first();

        $this->assertFalse($queue['is_patient_queue']);
        $this->assertNull($queue['patient_number']);
        $this->assertNull($queue['serviced_at_label']);
    }
}
