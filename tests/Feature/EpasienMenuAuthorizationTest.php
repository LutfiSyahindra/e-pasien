<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EpasienMenuAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('featureRoutes')]
    public function test_base_menu_access_does_not_bypass_feature_permissions(string $routeName): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('EPASIEN.MENU', 'web'));

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertForbidden();
    }

    public static function featureRoutes(): array
    {
        return [
            'online registration' => ['daftarOnline.index'],
            'doctor schedule' => ['jadwalDokter.index'],
            'room rates' => ['kamar.index'],
            'laboratory rates' => ['laboratorium.index'],
            'polyclinic rates' => ['poliklinik.index'],
            'radiology rates' => ['radiologi.index'],
            'laboratory requests' => ['pemeriksaanLaborat.index'],
            'radiology requests' => ['pemeriksaanRadiologi.index'],
            'prescriptions' => ['resepObat.index'],
            'operations' => ['operasi.index'],
            'examination history' => ['riwayatPemeriksaan.index'],
            'mcu history' => ['riwayatMcu.index'],
            'control letters' => ['suratKontrol.index'],
            'referral letters' => ['suratRujukan.index'],
        ];
    }
}
