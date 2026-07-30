<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\FasilitasTarif\LaboratoriumService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class LaboratoriumPageTest extends TestCase
{
    public function test_guest_cannot_access_laboratory_rate_page(): void
    {
        $this->get(route('laboratorium.index'))
            ->assertRedirect(route('login'));
    }

    public function test_patient_can_view_and_filter_mobile_friendly_laboratory_items(): void
    {
        $items = new LengthAwarePaginator([[
            'code' => 'J000111',
            'template_id' => '3273',
            'name' => 'Hemoglobin',
            'group_name' => 'DARAH LENGKAP',
            'unit' => 'g/dl',
            'has_unit' => true,
            'tariff' => 15000.0,
            'has_tariff' => true,
            'tariff_formatted' => 'Rp 15.000',
        ]], 1, 16);
        $summary = [
            'groups' => 51,
            'items' => 205,
            'priced' => 137,
            'minimum' => 1000.0,
            'maximum' => 3900000.0,
            'minimum_formatted' => 'Rp 1.000',
            'maximum_formatted' => 'Rp 3.900.000',
        ];

        $this->mock(LaboratoriumService::class, function (MockInterface $mock) use (
            $items,
            $summary
        ): void {
            $mock->shouldReceive('emptySummary')
                ->once()
                ->andReturn([
                    'groups' => 0,
                    'items' => 0,
                    'priced' => 0,
                    'minimum' => 0,
                    'maximum' => 0,
                    'minimum_formatted' => 'Rp 0',
                    'maximum_formatted' => 'Rp 0',
                ]);
            $mock->shouldReceive('items')
                ->once()
                ->with('J000111', 'hemoglobin')
                ->andReturn($items);
            $mock->shouldReceive('summary')
                ->once()
                ->andReturn($summary);
            $mock->shouldReceive('groups')
                ->once()
                ->andReturn(new Collection([[
                    'value' => 'J000111',
                    'label' => 'DARAH LENGKAP',
                    'total_items' => 10,
                ]]));
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('laboratorium.index', [
                'kelompok' => 'J000111',
                'q' => 'hemoglobin',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.FasilitasTarif.laboratorium.index')
            ->assertSeeText('Fasilitas & Tarif')
            ->assertSeeText('Laboratorium & Tarif')
            ->assertSeeText('Hemoglobin')
            ->assertSeeText('DARAH LENGKAP')
            ->assertSeeText('Rp 15.000')
            ->assertSeeText('Satuan hasil: g/dl')
            ->assertSeeText('Reset filter')
            ->assertSee('class="laboratory-rate-grid"', false)
            ->assertSee('name="kelompok"', false)
            ->assertSee('name="q"', false)
            ->assertSee('laboratorium-tarif.css');
    }

    public function test_laboratory_page_rejects_overlong_search(): void
    {
        $this->mock(LaboratoriumService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptySummary');
            $mock->shouldNotReceive('items');
            $mock->shouldNotReceive('summary');
            $mock->shouldNotReceive('groups');
        });

        $this->actingAs($this->patientUser())
            ->from(route('laboratorium.index'))
            ->get(route('laboratorium.index', ['q' => str_repeat('a', 81)]))
            ->assertRedirect(route('laboratorium.index'))
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
