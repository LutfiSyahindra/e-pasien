<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Services\epasien\settings\UserAccessMonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAccessMonitoringController extends Controller
{
    public function __construct(
        private readonly UserAccessMonitoringService $service,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'integer', 'in:7,30,90'],
            'channel' => ['nullable', 'in:all,web,pwa,installed'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view(
            'e-pasien.settings.userAccessMonitoring.index',
            $this->service->dashboard($filters),
        );
    }
}
