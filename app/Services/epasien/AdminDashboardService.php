<?php

namespace App\Services\epasien;

use App\Models\BpjsApiLog;
use App\Models\OnlineRegistrationAudit;
use App\Models\PatientServiceConversation;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserAccessDaily;
use App\Models\UserAccessDevice;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    private const TIMEZONE = 'Asia/Jakarta';

    /**
     * @return array<string, mixed>
     */
    public function overview(CarbonInterface $at): array
    {
        $now = CarbonImmutable::instance($at)->setTimezone(self::TIMEZONE);
        $today = $now->toDateString();
        $from = $now->startOfDay()->subDays(6);
        $dayStartUtc = $now->startOfDay()->utc();
        $dayEndUtc = $now->endOfDay()->utc();

        $registrationRows = OnlineRegistrationAudit::query()
            ->whereBetween('registration_date', [$from->toDateString(), $today])
            ->select('registration_date')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('registration_date')
            ->get()
            ->keyBy(fn (OnlineRegistrationAudit $row): string => $row->registration_date->toDateString());

        $accessRows = UserAccessDaily::query()
            ->whereBetween('access_date', [$from->toDateString(), $today])
            ->select('access_date')
            ->selectRaw('COUNT(DISTINCT user_id) AS total')
            ->groupBy('access_date')
            ->get()
            ->keyBy(fn (UserAccessDaily $row): string => (string) $row->access_date);

        $trend = collect(range(0, 6))->map(function (int $offset) use ($from, $registrationRows, $accessRows): array {
            $date = $from->addDays($offset);
            $dateKey = $date->toDateString();

            return [
                'date' => $dateKey,
                'label' => $date->locale('id')->translatedFormat('D'),
                'date_label' => $date->locale('id')->translatedFormat('d M'),
                'registrations' => (int) ($registrationRows->get($dateKey)?->total ?? 0),
                'active_users' => (int) ($accessRows->get($dateKey)?->total ?? 0),
            ];
        });

        $patientRoleNames = array_values(array_unique([
            config('access-control.patient_role', 'Patient'),
            ...config('access-control.patient_role_aliases', ['Pasien']),
        ]));

        $waitingAdmin = PatientServiceConversation::query()
            ->where('status', PatientServiceConversation::STATUS_WAITING_ADMIN)
            ->count();

        return [
            'summary' => [
                'patient_users' => User::query()
                    ->where('status', true)
                    ->whereHas('roles', fn ($query) => $query->whereIn('name', $patientRoleNames))
                    ->count(),
                'registrations_today' => OnlineRegistrationAudit::query()
                    ->where('registration_date', $today)
                    ->count(),
                'active_users_today' => UserAccessDaily::query()
                    ->where('access_date', $today)
                    ->distinct()
                    ->count('user_id'),
                'waiting_tickets' => $waitingAdmin,
            ],
            'system' => [
                'active_accounts' => User::query()->where('status', true)->count(),
                'new_accounts_today' => User::query()
                    ->whereBetween('created_at', [$dayStartUtc, $dayEndUtc])
                    ->count(),
                'pwa_installations' => UserAccessDevice::query()
                    ->where('is_pwa_installed', true)
                    ->count(),
                'active_promotions' => Promotion::query()->active($now)->count(),
                'bpjs_errors_today' => BpjsApiLog::query()
                    ->whereBetween('created_at', [$dayStartUtc, $dayEndUtc])
                    ->where(function ($query): void {
                        $query->whereNotNull('error_message')
                            ->orWhere('http_code', '>=', 400);
                    })
                    ->count(),
            ],
            'service' => [
                'waiting_admin' => $waitingAdmin,
                'waiting_patient' => PatientServiceConversation::query()
                    ->where('status', PatientServiceConversation::STATUS_WAITING_PATIENT)
                    ->count(),
                'closed_today' => PatientServiceConversation::query()
                    ->whereBetween('closed_at', [$dayStartUtc, $dayEndUtc])
                    ->count(),
            ],
            'trend' => $trend,
            'trendTotals' => [
                'registrations' => $trend->sum('registrations'),
                'active_users' => $this->uniqueActiveUsers($from->toDateString(), $today),
            ],
            'registrationPeak' => max(1, (int) $trend->max('registrations')),
            'activeUserPeak' => max(1, (int) $trend->max('active_users')),
            'topClinics' => $this->topClinics($today),
            'recentRegistrations' => OnlineRegistrationAudit::query()
                ->latest('registration_date')
                ->latest('registration_time')
                ->limit(5)
                ->get(),
            'recentConversations' => PatientServiceConversation::query()
                ->with('patient:id,name,username,profile_photo_path')
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [PatientServiceConversation::STATUS_WAITING_ADMIN])
                ->latest('last_message_at')
                ->limit(5)
                ->get(),
            'recentUsers' => User::query()
                ->with('roles:id,name')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    private function uniqueActiveUsers(string $from, string $until): int
    {
        return UserAccessDaily::query()
            ->whereBetween('access_date', [$from, $until])
            ->distinct()
            ->count('user_id');
    }

    /**
     * @return Collection<int, object>
     */
    private function topClinics(string $today): Collection
    {
        return OnlineRegistrationAudit::query()
            ->where('registration_date', $today)
            ->select(['clinic_code', 'clinic_name'])
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('clinic_code', 'clinic_name')
            ->orderByDesc('total')
            ->limit(4)
            ->get();
    }
}
