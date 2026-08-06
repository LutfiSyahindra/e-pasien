<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use App\Services\LandingPage\LandingDoctorService;
use Illuminate\Support\Facades\Log;
use Throwable;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingDoctorService $landingDoctorService
    ) {}

    public function index()
    {
        $landingDoctors = [];

        try {
            $landingDoctors = $this->landingDoctorService->featured();
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat dokter untuk landing page.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return view('landingPage.landingPage', compact('landingDoctors'));
    }
}
