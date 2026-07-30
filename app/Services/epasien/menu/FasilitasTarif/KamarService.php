<?php

namespace App\Services\epasien\menu\FasilitasTarif;

use App\Repositories\epasien\menu\FasilitasTarif\KamarRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class KamarService
{
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
        $rooms = $this->kamarRepository->paginateRooms(
            $this->databaseStatus($status),
            $this->nullableText($class),
            $this->nullableText($search),
            $perPage
        );

        $rooms->setCollection(
            $rooms->getCollection()->map(
                fn (object $room): array => $this->formatRoom($room)
            )
        );

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
        return $this->kamarRepository->roomCounts();
    }

    /**
     * @return Collection<int, string>
     */
    public function classes(): Collection
    {
        return $this->kamarRepository->roomClasses();
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
