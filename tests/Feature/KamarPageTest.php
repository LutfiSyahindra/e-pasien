<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\FasilitasTarif\KamarService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class KamarPageTest extends TestCase
{
    public function test_guest_cannot_access_room_page(): void
    {
        $this->get(route('kamar.index'))
            ->assertRedirect(route('login'));
    }

    public function test_patient_can_view_and_filter_mobile_friendly_room_information(): void
    {
        $rooms = new LengthAwarePaginator([[
            'code' => 'Aisyah.VVIP.A',
            'ward_code' => 'VVIP',
            'ward' => 'Aisyah VVIP',
            'class' => 'Kelas VVIP',
            'tariff' => 750000.0,
            'tariff_formatted' => 'Rp 750.000',
            'status' => 'tersedia',
            'status_label' => 'Tersedia',
            'status_icon' => 'bi-check-circle-fill',
        ]], 1, 12);
        $counts = [
            'all' => 125,
            'available' => 71,
            'occupied' => 52,
            'cleaning' => 1,
            'booked' => 1,
        ];

        $this->mock(KamarService::class, function (MockInterface $mock) use (
            $rooms,
            $counts
        ): void {
            $mock->shouldReceive('emptyCounts')
                ->once()
                ->andReturn([
                    'all' => 0,
                    'available' => 0,
                    'occupied' => 0,
                    'cleaning' => 0,
                    'booked' => 0,
                ]);
            $mock->shouldReceive('rooms')
                ->once()
                ->with('tersedia', 'Kelas VVIP', 'aisyah')
                ->andReturn($rooms);
            $mock->shouldReceive('counts')
                ->once()
                ->andReturn($counts);
            $mock->shouldReceive('classes')
                ->once()
                ->andReturn(new Collection([
                    'Kelas 1',
                    'Kelas VIP',
                    'Kelas VVIP',
                ]));
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('kamar.index', [
                'status' => 'tersedia',
                'kelas' => 'Kelas VVIP',
                'q' => 'aisyah',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.FasilitasTarif.kamar.index')
            ->assertSeeText('Fasilitas & Tarif')
            ->assertSeeText('Kamar & Tarif')
            ->assertSeeText('Aisyah.VVIP.A')
            ->assertSeeText('Aisyah VVIP')
            ->assertSeeText('Rp 750.000')
            ->assertSeeText('Tersedia')
            ->assertSeeText('Reset filter')
            ->assertSee('class="room-grid"', false)
            ->assertSee('name="kelas"', false)
            ->assertSee('name="q"', false);
    }

    public function test_room_page_rejects_invalid_status_filter(): void
    {
        $this->mock(KamarService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptyCounts');
            $mock->shouldNotReceive('rooms');
            $mock->shouldNotReceive('counts');
            $mock->shouldNotReceive('classes');
        });

        $this->actingAs($this->patientUser())
            ->from(route('kamar.index'))
            ->get(route('kamar.index', ['status' => 'tidak-valid']))
            ->assertRedirect(route('kamar.index'))
            ->assertSessionHasErrors('status');
    }

    private function patientUser(): User
    {
        $user = new User([
            'name' => 'Budi Santoso',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        return $user;
    }
}
