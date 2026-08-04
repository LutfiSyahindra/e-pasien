<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\FasilitasTarif\RadiologiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\Concerns\AuthorizesEpasienMenuRoutes;
use Tests\TestCase;

class RadiologiPageTest extends TestCase
{
    use AuthorizesEpasienMenuRoutes;

    public function test_guest_cannot_access_radiology_rate_page(): void
    {
        $this->get(route('radiologi.index'))
            ->assertRedirect(route('login'));
    }

    public function test_patient_can_view_and_filter_mobile_friendly_radiology_rates(): void
    {
        $rates = new LengthAwarePaginator([[
            'code' => 'RAD001',
            'name' => 'Foto Thorax PA',
            'class' => 'Rawat Jalan',
            'class_label' => 'Rawat Jalan',
            'tariff' => 175000.0,
            'tariff_formatted' => 'Rp 175.000',
        ]], 1, 12);
        $summary = [
            'total' => 45,
            'minimum' => 75000.0,
            'maximum' => 1500000.0,
            'minimum_formatted' => 'Rp 75.000',
            'maximum_formatted' => 'Rp 1.500.000',
        ];

        $this->mock(RadiologiService::class, function (MockInterface $mock) use (
            $rates,
            $summary
        ): void {
            $mock->shouldReceive('emptySummary')
                ->once()
                ->andReturn([
                    'total' => 0,
                    'minimum' => 0,
                    'maximum' => 0,
                    'minimum_formatted' => 'Rp 0',
                    'maximum_formatted' => 'Rp 0',
                ]);
            $mock->shouldReceive('rates')
                ->once()
                ->with('Rawat Jalan', 'thorax')
                ->andReturn($rates);
            $mock->shouldReceive('summary')
                ->once()
                ->andReturn($summary);
            $mock->shouldReceive('classes')
                ->once()
                ->andReturn(new Collection([
                    [
                        'value' => 'Rawat Jalan',
                        'label' => 'Rawat Jalan',
                    ],
                ]));
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('radiologi.index', [
                'kelas' => 'Rawat Jalan',
                'q' => 'thorax',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.FasilitasTarif.radiologi.index')
            ->assertSeeText('Fasilitas & Tarif')
            ->assertSeeText('Radiologi & Tarif')
            ->assertSeeText('Foto Thorax PA')
            ->assertSeeText('Rp 175.000')
            ->assertSeeText('Reset filter')
            ->assertSee('class="radiology-rate-grid"', false)
            ->assertSee('name="kelas"', false)
            ->assertSee('name="q"', false)
            ->assertSee('radiologi-tarif.css');
    }

    public function test_radiology_page_rejects_overlong_search(): void
    {
        $this->mock(RadiologiService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptySummary');
            $mock->shouldNotReceive('rates');
            $mock->shouldNotReceive('summary');
            $mock->shouldNotReceive('classes');
        });

        $this->actingAs($this->patientUser())
            ->from(route('radiologi.index'))
            ->get(route('radiologi.index', ['q' => str_repeat('a', 81)]))
            ->assertRedirect(route('radiologi.index'))
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
