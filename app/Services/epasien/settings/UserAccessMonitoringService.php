<?php

namespace App\Services\epasien\settings;

use App\Models\User;
use App\Models\UserAccessDaily;
use App\Models\UserAccessDevice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UserAccessMonitoringService
{
    /**
     * @param  array{device_uuid:string, mode:string, installed:bool}  $data
     */
    public function record(User $user, array $data, ?string $userAgent): UserAccessDevice
    {
        return DB::transaction(function () use ($user, $data, $userAgent): UserAccessDevice {
            $now = now();
            $mode = $data['mode'];
            $installed = $data['installed'] || $mode === UserAccessDevice::MODE_PWA;
            $client = $this->clientDetails($userAgent);

            $device = UserAccessDevice::query()->firstOrCreate([
                'user_id' => $user->getKey(),
                'device_uuid' => $data['device_uuid'],
            ], [
                'platform' => $client['platform'],
                'browser' => $client['browser'],
                'last_mode' => $mode,
                'is_pwa_installed' => false,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            $device->platform = $client['platform'];
            $device->browser = $client['browser'];
            $device->last_mode = $mode;
            $device->last_seen_at = $now;

            if ($mode === UserAccessDevice::MODE_PWA) {
                $device->last_pwa_seen_at = $now;
            } else {
                $device->last_web_seen_at = $now;
            }

            if ($installed && ! $device->is_pwa_installed) {
                $device->is_pwa_installed = true;
                $device->installed_at = $now;
            }

            $device->save();

            $daily = UserAccessDaily::query()->firstOrCreate([
                'user_access_device_id' => $device->getKey(),
                'access_date' => $now->toDateString(),
                'mode' => $mode,
            ], [
                'user_id' => $user->getKey(),
                'visit_count' => 0,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            $daily->increment('visit_count');
            $daily->update(['last_seen_at' => $now]);

            return $device->refresh();
        }, 3);
    }

    /**
     * @param  array{period?:int|string, channel?:string, q?:string}  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters): array
    {
        $requestedPeriod = (int) ($filters['period'] ?? 30);
        $requestedChannel = (string) ($filters['channel'] ?? 'all');
        $period = in_array($requestedPeriod, [7, 30, 90], true)
            ? $requestedPeriod
            : 30;
        $channel = in_array($requestedChannel, ['all', 'web', 'pwa', 'installed'], true)
            ? $requestedChannel
            : 'all';
        $search = trim((string) ($filters['q'] ?? ''));
        $today = CarbonImmutable::today();
        $from = $today->subDays($period - 1);

        $activity = UserAccessDaily::query()
            ->whereBetween('access_date', [$from->toDateString(), $today->toDateString()]);

        $activeUsers = (clone $activity)->distinct()->count('user_id');
        $webUsers = (clone $activity)
            ->where('mode', UserAccessDevice::MODE_WEB)
            ->distinct()
            ->count('user_id');
        $pwaUsers = (clone $activity)
            ->where('mode', UserAccessDevice::MODE_PWA)
            ->distinct()
            ->count('user_id');
        $installedUsers = UserAccessDevice::query()
            ->where('is_pwa_installed', true)
            ->distinct()
            ->count('user_id');
        $trackedUsers = UserAccessDevice::query()->distinct()->count('user_id');

        $seriesRows = (clone $activity)
            ->select(['access_date', 'mode'])
            ->selectRaw('COUNT(DISTINCT user_id) AS users')
            ->groupBy('access_date', 'mode')
            ->get()
            ->keyBy(fn (UserAccessDaily $row): string => $row->access_date.'|'.$row->mode);

        $series = collect(range(0, $period - 1))->map(function (int $offset) use ($from, $seriesRows): array {
            $date = $from->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'label' => $date->locale('id')->translatedFormat('d M'),
                'web' => (int) ($seriesRows->get($date->toDateString().'|web')?->users ?? 0),
                'pwa' => (int) ($seriesRows->get($date->toDateString().'|pwa')?->users ?? 0),
            ];
        });

        $deviceQuery = UserAccessDevice::query()
            ->with(['user:id,name,username,email,profile_photo_path'])
            ->where('last_seen_at', '>=', $from->startOfDay());

        if ($channel === 'web') {
            $deviceQuery->where('last_web_seen_at', '>=', $from->startOfDay());
        } elseif ($channel === 'pwa') {
            $deviceQuery->where('last_pwa_seen_at', '>=', $from->startOfDay());
        } elseif ($channel === 'installed') {
            $deviceQuery->where('is_pwa_installed', true);
        }

        if ($search !== '') {
            $deviceQuery->whereHas('user', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            });
        }

        $devices = $deviceQuery
            ->latest('last_seen_at')
            ->paginate(12)
            ->withQueryString();

        $platforms = UserAccessDevice::query()
            ->where('last_seen_at', '>=', $from->startOfDay())
            ->select('platform')
            ->selectRaw('COUNT(*) AS devices')
            ->groupBy('platform')
            ->orderByDesc('devices')
            ->get()
            ->map(fn (UserAccessDevice $device): array => [
                'label' => $device->platform ?: 'Lainnya',
                'devices' => (int) $device->devices,
            ]);

        return [
            'filters' => [
                'period' => $period,
                'channel' => $channel,
                'q' => $search,
            ],
            'periodLabel' => $from->locale('id')->translatedFormat('d M Y')
                .' – '.$today->locale('id')->translatedFormat('d M Y'),
            'summary' => [
                'registered_users' => User::query()
                    ->where(fn ($query) => $query->where('status', true)->orWhereNull('status'))
                    ->count(),
                'active_users' => $activeUsers,
                'web_users' => $webUsers,
                'pwa_users' => $pwaUsers,
                'installed_users' => $installedUsers,
                'installed_devices' => UserAccessDevice::query()->where('is_pwa_installed', true)->count(),
                'conversion_rate' => $trackedUsers > 0
                    ? round(($installedUsers / $trackedUsers) * 100, 1)
                    : 0,
            ],
            'series' => $series,
            'seriesPeak' => max(1, (int) $series->max(fn (array $day): int => max($day['web'], $day['pwa']))),
            'platforms' => $platforms,
            'platformPeak' => max(1, (int) $platforms->max('devices')),
            'devices' => $devices,
        ];
    }

    /**
     * @return array{platform:string, browser:string}
     */
    private function clientDetails(?string $userAgent): array
    {
        $agent = (string) $userAgent;

        $platform = match (true) {
            preg_match('/android/i', $agent) === 1 => 'Android',
            preg_match('/iphone|ipad|ipod/i', $agent) === 1 => 'iOS',
            preg_match('/windows/i', $agent) === 1 => 'Windows',
            preg_match('/macintosh|mac os x/i', $agent) === 1 => 'macOS',
            preg_match('/linux/i', $agent) === 1 => 'Linux',
            default => 'Lainnya',
        };

        $browser = match (true) {
            preg_match('/samsungbrowser/i', $agent) === 1 => 'Samsung Internet',
            preg_match('/edg|edgios|edga/i', $agent) === 1 => 'Microsoft Edge',
            preg_match('/crios|chrome/i', $agent) === 1 => 'Google Chrome',
            preg_match('/fxios|firefox/i', $agent) === 1 => 'Mozilla Firefox',
            preg_match('/safari/i', $agent) === 1 => 'Safari',
            default => 'Lainnya',
        };

        return compact('platform', 'browser');
    }
}
