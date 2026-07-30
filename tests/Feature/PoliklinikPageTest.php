<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\FasilitasTarif\PoliklinikService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class PoliklinikPageTest extends TestCase
{
    public function test_guest_cannot_access_polyclinic_rate_page(): void
    {
        $this->get(route('poliklinik.index'))
            ->assertRedirect(route('login'));
    }

    public function test_patient_can_search_mobile_friendly_polyclinic_information(): void
    {
        $clinics = new LengthAwarePaginator([[
            'code' => 'ANA',
            'name' => 'Poliklinik Anak',
            'icon' => 'bi-emoji-smile',
            'new_fee' => 5000.0,
            'new_fee_available' => true,
            'new_fee_formatted' => 'Rp 5.000',
            'returning_fee' => 0.0,
            'returning_fee_available' => false,
            'returning_fee_formatted' => 'Konfirmasi tarif',
        ]], 1, 12);
        $summary = [
            'total' => 12,
            'priced' => 12,
            'minimum' => 5000.0,
            'maximum' => 5000.0,
            'minimum_formatted' => 'Rp 5.000',
            'maximum_formatted' => 'Rp 5.000',
        ];

        $this->mock(PoliklinikService::class, function (MockInterface $mock) use (
            $clinics,
            $summary
        ): void {
            $mock->shouldReceive('emptySummary')
                ->once()
                ->andReturn([
                    'total' => 0,
                    'priced' => 0,
                    'minimum' => 0,
                    'maximum' => 0,
                    'minimum_formatted' => 'Rp 0',
                    'maximum_formatted' => 'Rp 0',
                ]);
            $mock->shouldReceive('clinics')
                ->once()
                ->with('anak')
                ->andReturn($clinics);
            $mock->shouldReceive('summary')
                ->once()
                ->andReturn($summary);
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('poliklinik.index', ['q' => 'anak'])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.FasilitasTarif.poliklinik.index')
            ->assertSeeText('Fasilitas & Tarif')
            ->assertSeeText('Poliklinik & Tarif')
            ->assertSeeText('Poliklinik spesialis')
            ->assertSeeText('Poliklinik Anak')
            ->assertSeeText('Registrasi pasien baru')
            ->assertSeeText('Rp 5.000')
            ->assertSeeText('Konfirmasi tarif')
            ->assertSeeText('Lihat jadwal & daftar')
            ->assertSeeText('Reset pencarian')
            ->assertSee('class="polyclinic-rate-grid"', false)
            ->assertSee('name="q"', false)
            ->assertSee('poliklinik-tarif.css');
    }

    public function test_polyclinic_page_rejects_overlong_search(): void
    {
        $this->mock(PoliklinikService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptySummary');
            $mock->shouldNotReceive('clinics');
            $mock->shouldNotReceive('summary');
        });

        $this->actingAs($this->patientUser())
            ->from(route('poliklinik.index'))
            ->get(route('poliklinik.index', ['q' => str_repeat('a', 81)]))
            ->assertRedirect(route('poliklinik.index'))
            ->assertSessionHasErrors('q');
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
