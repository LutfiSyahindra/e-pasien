<?php

namespace Tests\Unit\Services\Epasien\Menu;

use App\Repositories\epasien\menu\FasilitasTarif\KamarRepository;
use App\Services\epasien\menu\FasilitasTarif\KamarService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class KamarServiceTest extends TestCase
{
    public function test_rooms_are_filtered_and_formatted_for_patients(): void
    {
        $repository = $this->createMock(KamarRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateRooms')
            ->with(
                'KOSONG',
                'Kelas VVIP',
                'aisyah',
                12
            )
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_kamar' => 'Aisyah.VVIP.A',
                    'kd_bangsal' => 'VVIP',
                    'nm_bangsal' => 'Aisyah VVIP',
                    'trf_kamar' => 750000,
                    'status' => 'KOSONG',
                    'kelas' => 'Kelas VVIP',
                ],
            ], 1, 12));

        $rooms = (new KamarService($repository))->rooms(
            'tersedia',
            ' Kelas VVIP ',
            ' aisyah '
        );
        $room = $rooms->items()[0];

        $this->assertSame('Aisyah.VVIP.A', $room['code']);
        $this->assertSame('Aisyah VVIP', $room['ward']);
        $this->assertSame('Rp 750.000', $room['tariff_formatted']);
        $this->assertSame('tersedia', $room['status']);
        $this->assertSame('Tersedia', $room['status_label']);
        $this->assertSame('bi-check-circle-fill', $room['status_icon']);
    }

    public function test_all_database_room_statuses_have_patient_friendly_labels(): void
    {
        $repository = $this->createMock(KamarRepository::class);
        $repository
            ->expects($this->once())
            ->method('paginateRooms')
            ->with(null, null, null, 12)
            ->willReturn(new LengthAwarePaginator([
                $this->room('ISI'),
                $this->room('DIBERSIHKAN'),
                $this->room('DIBOOKING'),
            ], 3, 12));

        $rooms = (new KamarService($repository))->rooms()->items();

        $this->assertSame('Terisi', $rooms[0]['status_label']);
        $this->assertSame('Sedang dibersihkan', $rooms[1]['status_label']);
        $this->assertSame('Sudah dipesan', $rooms[2]['status_label']);
    }

    private function room(string $status): object
    {
        return (object) [
            'kd_kamar' => 'Kamar.01',
            'kd_bangsal' => 'K1',
            'nm_bangsal' => 'Bangsal Utama',
            'trf_kamar' => 100000,
            'status' => $status,
            'kelas' => 'Kelas 1',
        ];
    }
}
