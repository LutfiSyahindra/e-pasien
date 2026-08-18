<?php

namespace App\Http\Controllers\Epasien;

use App\Http\Controllers\Controller;
use App\Models\UserAccessDevice;
use App\Services\epasien\settings\UserAccessMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserAccessController extends Controller
{
    public function __construct(
        private readonly UserAccessMonitoringService $service,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_uuid' => ['required', 'uuid'],
            'mode' => ['required', Rule::in([
                UserAccessDevice::MODE_WEB,
                UserAccessDevice::MODE_PWA,
            ])],
            'installed' => ['required', 'boolean'],
        ]);

        $this->service->record($request->user(), $validated, $request->userAgent());

        return response()->json(['recorded' => true]);
    }
}
