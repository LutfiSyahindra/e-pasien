<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epasien\Settings\UpdateLandingPageRequest;
use App\Services\LandingPage\LandingPageContentService;
use Illuminate\Support\Facades\Log;
use Throwable;

class LandingPageSettingController extends Controller
{
    public function __construct(private readonly LandingPageContentService $contentService) {}

    public function edit()
    {
        return view('e-pasien.settings.landingPage.edit', $this->contentService->current());
    }

    public function update(UpdateLandingPageRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->contentService->update(
                $validated['content'],
                array_filter($validated['images'] ?? []),
                $validated['reset_images'] ?? [],
                $request->user()->id
            );
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan informasi landing page.', ['user_id' => $request->user()->id, 'message' => $exception->getMessage()]);

            return back()->withInput()->withErrors(['landing_page' => 'Informasi belum dapat disimpan. Silakan coba lagi.']);
        }

        return redirect()->route('landingPageSettings.edit')->with('status', 'Informasi landing page berhasil disimpan dan langsung tampil di halaman publik.');
    }
}
