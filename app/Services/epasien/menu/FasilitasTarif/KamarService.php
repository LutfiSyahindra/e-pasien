<?php

namespace App\Services\epasien\menu\FasilitasTarif;

use App\Repositories\epasien\menu\FasilitasTarif\KamarRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class KamarService
{
    private const LIVE_CACHE_FRESH_SECONDS = 10;

    private const LIVE_CACHE_STALE_SECONDS = 60;

    private const REFERENCE_CACHE_FRESH_SECONDS = 900;

    private const REFERENCE_CACHE_STALE_SECONDS = 3600;

    private const STATUS_MAP = [
        'tersedia' => 'KOSONG',
        'terisi' => 'ISI',
        'dibersihkan' => 'DIBERSIHKAN',
        'dipesan' => 'DIBOOKING',
    ];

    public function __construct(
        private readonly KamarRepository $kamarRepository
    ) {}

    public function rooms(
        ?string $status = null,
        ?string $class = null,
        ?string $search = null,
        int $perPage = 12
    ): LengthAwarePaginator {
        $perPage = max(6, min($perPage, 24));
        $databaseStatus = $this->databaseStatus($status);
        $class = $this->nullableText($class);
        $search = $this->nullableText($search);
        $rooms = Cache::flexible(
            $this->roomsCacheKey($databaseStatus, $class, $search, $perPage),
            [
                self::LIVE_CACHE_FRESH_SECONDS,
                self::LIVE_CACHE_STALE_SECONDS,
            ],
            function () use ($databaseStatus, $class, $search, $perPage): LengthAwarePaginator {
                $rooms = $this->kamarRepository->paginateRooms(
                    $databaseStatus,
                    $class,
                    $search,
                    $perPage
                );
                $rooms->setCollection(
                    $rooms->getCollection()->map(
                        fn (object $room): array => $this->formatRoom($room)
                    )
                );

                return $rooms;
            }
        );
        $rooms->setPath(LengthAwarePaginator::resolveCurrentPath());

        return $rooms;
    }

    /**
     * @return array{
     *     all: int,
     *     available: int,
     *     occupied: int,
     *     cleaning: int,
     *     booked: int
     * }
     */
    public function counts(): array
    {
        return Cache::flexible(
            'epasien:khanza:rooms:counts:v1',
            [
                self::LIVE_CACHE_FRESH_SECONDS,
                self::LIVE_CACHE_STALE_SECONDS,
            ],
            fn (): array => $this->kamarRepository->roomCounts()
        );
    }

    /**
     * @return Collection<int, string>
     */
    public function classes(): Collection
    {
        return Cache::flexible(
            'epasien:khanza:rooms:classes:v1',
            [
                self::REFERENCE_CACHE_FRESH_SECONDS,
                self::REFERENCE_CACHE_STALE_SECONDS,
            ],
            fn (): Collection => $this->kamarRepository->roomClasses()
        );
    }

    private function roomsCacheKey(
        ?string $status,
        ?string $class,
        ?string $search,
        int $perPage
    ): string {
        $filters = json_encode([
            'status' => $status,
            'class' => $class,
            'search' => $search,
            'per_page' => $perPage,
            'page' => LengthAwarePaginator::resolveCurrentPage(),
        ], JSON_THROW_ON_ERROR);

        return 'epasien:khanza:rooms:list:v2:'.hash('sha256', $filters);
    }

    /**
     * @return array{
     *     all: int,
     *     available: int,
     *     occupied: int,
     *     cleaning: int,
     *     booked: int
     * }
     */
    public function emptyCounts(): array
    {
        return [
            'all' => 0,
            'available' => 0,
            'occupied' => 0,
            'cleaning' => 0,
            'booked' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRoom(object $room): array
    {
        $status = mb_strtoupper(trim((string) ($room->status ?? '')));
        $tariff = max(0, (float) ($room->trf_kamar ?? 0));

        return [
            'code' => $this->text($room->kd_kamar ?? null),
            'ward_code' => $this->text($room->kd_bangsal ?? null),
            'ward' => $this->text($room->nm_bangsal ?? null),
            'class' => $this->text($room->kelas ?? null),
            'tariff' => $tariff,
            'tariff_formatted' => 'Rp '.number_format(
                $tariff,
                0,
                ',',
                '.'
            ),
            'status' => $this->statusSlug($status),
            'status_label' => $this->statusLabel($status),
            'status_icon' => $this->statusIcon($status),
        ];
    }

    private function databaseStatus(?string $status): ?string
    {
        $status = $this->nullableText($status);

        return $status !== null
            ? (self::STATUS_MAP[$status] ?? null)
            : null;
    }

    private function statusSlug(string $status): string
    {
        return match ($status) {
            'KOSONG' => 'tersedia',
            'ISI' => 'terisi',
            'DIBERSIHKAN' => 'dibersihkan',
            'DIBOOKING' => 'dipesan',
            default => 'lainnya',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'KOSONG' => 'Tersedia',
            'ISI' => 'Terisi',
            'DIBERSIHKAN' => 'Sedang dibersihkan',
            'DIBOOKING' => 'Sudah dipesan',
            default => 'Status belum tersedia',
        };
    }

    private function statusIcon(string $status): string
    {
        return match ($status) {
            'KOSONG' => 'bi-check-circle-fill',
            'ISI' => 'bi-person-fill',
            'DIBERSIHKAN' => 'bi-stars',
            'DIBOOKING' => 'bi-calendar2-check-fill',
            default => 'bi-question-circle',
        };
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function text(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : '-';
    }
}
