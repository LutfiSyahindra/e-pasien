<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epasien\Menu\UpdatePromotionConfigurationRequest;
use App\Models\PromotionConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PromotionConfigurationController extends Controller
{
    public function edit(): View
    {
        return view('e-pasien.menu.promotions.configuration', [
            'configuration' => PromotionConfiguration::current(),
        ]);
    }

    public function update(UpdatePromotionConfigurationRequest $request): RedirectResponse
    {
        PromotionConfiguration::current()->update([
            ...$request->validated(),
            'auto_delete_enabled' => true,
            'delete_grace_value' => 0,
            'delete_grace_unit' => 'hour',
            'configured_by' => $request->user()->getKey(),
        ]);

        return back()->with('success', 'Konfigurasi Promosi & Informasi berhasil disimpan.');
    }
}
