<?php

namespace App\Services\epasien\Profile;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PatientProfileService
{
    private const PROFILE_COLUMNS = [
        'no_rkm_medis',
        'nm_pasien',
        'no_ktp',
        'jk',
        'tmp_lahir',
        'tgl_lahir',
        'nm_ibu',
        'alamat',
        'gol_darah',
        'pekerjaan',
        'stts_nikah',
        'agama',
        'tgl_daftar',
        'no_tlp',
        'umur',
        'pnd',
        'keluarga',
        'namakeluarga',
        'kd_pj',
        'no_peserta',
        'pekerjaanpj',
        'alamatpj',
        'kelurahanpj',
        'kecamatanpj',
        'kabupatenpj',
        'perusahaan_pasien',
        'suku_bangsa',
        'bahasa_pasien',
        'cacat_fisik',
        'email',
        'nip',
        'propinsipj',
    ];

    public function findForUser(User $user): ?object
    {
        $medicalRecordNumber = trim((string) $user->username);

        if ($medicalRecordNumber === '') {
            return null;
        }

        try {
            $columns = $this->availablePatientColumns();

            if (! in_array('no_rkm_medis', $columns, true)) {
                return null;
            }

            $selectColumns = array_values(array_intersect(self::PROFILE_COLUMNS, $columns));

            return DB::connection('mysql_khanza')
                ->table('pasien')
                ->select($selectColumns ?: ['no_rkm_medis'])
                ->where('no_rkm_medis', $medicalRecordNumber)
                ->first();
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat data profil pasien.', [
                'user_id' => $user->id,
                'username' => $medicalRecordNumber,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function overview(?object $patient): array
    {
        return [
            [
                'label' => 'No. Rekam Medis',
                'value' => $this->value($patient, 'no_rkm_medis'),
                'icon' => 'bi-upc-scan',
                'tone' => 'blue',
            ],
            [
                'label' => 'Tanggal Daftar',
                'value' => $this->value($patient, 'tgl_daftar'),
                'icon' => 'bi-calendar2-check',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Kontak Utama',
                'value' => $this->value($patient, 'no_tlp'),
                'icon' => 'bi-telephone',
                'tone' => 'amber',
            ],
            [
                'label' => 'Kepesertaan',
                'value' => $this->value($patient, 'no_peserta'),
                'icon' => 'bi-credit-card-2-front',
                'tone' => 'rose',
            ],
        ];
    }

    public function detailGroups(?object $patient): array
    {
        return [
            [
                'title' => 'Identitas Pasien',
                'icon' => 'bi-person-badge',
                'items' => [
                    $this->item($patient, 'nm_pasien', 'Nama lengkap', 'bi-person'),
                    $this->item($patient, 'no_ktp', 'NIK / No. KTP', 'bi-card-text'),
                    $this->item($patient, 'jk', 'Jenis kelamin', 'bi-gender-ambiguous'),
                    $this->item($patient, 'tmp_lahir', 'Tempat lahir', 'bi-geo-alt'),
                    $this->item($patient, 'tgl_lahir', 'Tanggal lahir', 'bi-calendar-heart'),
                    $this->item($patient, 'umur', 'Umur', 'bi-hourglass-split'),
                ],
            ],
            [
                'title' => 'Kontak & Domisili',
                'icon' => 'bi-house-heart',
                'items' => [
                    $this->item($patient, 'alamat', 'Alamat', 'bi-map'),
                    $this->item($patient, 'no_tlp', 'Nomor telepon', 'bi-telephone'),
                    $this->item($patient, 'email', 'Email pasien', 'bi-envelope'),
                    $this->item($patient, 'kelurahanpj', 'Kelurahan PJ', 'bi-pin-map'),
                    $this->item($patient, 'kecamatanpj', 'Kecamatan PJ', 'bi-signpost'),
                    $this->item($patient, 'kabupatenpj', 'Kabupaten PJ', 'bi-building'),
                    $this->item($patient, 'propinsipj', 'Provinsi PJ', 'bi-compass'),
                ],
            ],
            [
                'title' => 'Keluarga & Penanggung Jawab',
                'icon' => 'bi-people',
                'items' => [
                    $this->item($patient, 'nm_ibu', 'Nama ibu', 'bi-person-heart'),
                    $this->item($patient, 'keluarga', 'Status keluarga', 'bi-diagram-3'),
                    $this->item($patient, 'namakeluarga', 'Nama keluarga', 'bi-person-lines-fill'),
                    $this->item($patient, 'pekerjaanpj', 'Pekerjaan PJ', 'bi-briefcase'),
                    $this->item($patient, 'alamatpj', 'Alamat PJ', 'bi-map-fill'),
                ],
            ],
        ];
    }

    public function completion(?object $patient): int
    {
        if (! $patient) {
            return 0;
        }

        $values = collect(get_object_vars($patient))
            ->reject(fn (mixed $value, string $key): bool => $key === 'no_rkm_medis');

        if ($values->isEmpty()) {
            return 0;
        }

        $filled = $values->filter(fn (mixed $value): bool => $this->filled($value))->count();

        return (int) round(($filled / $values->count()) * 100);
    }

    private function availablePatientColumns(): array
    {
        return DB::connection('mysql_khanza')
            ->getSchemaBuilder()
            ->getColumnListing('pasien');
    }

    private function item(?object $patient, string $column, string $label, string $icon): array
    {
        return [
            'label' => $label,
            'value' => $this->value($patient, $column),
            'icon' => $icon,
        ];
    }

    private function value(?object $patient, string $column): ?string
    {
        if (! $patient || ! property_exists($patient, $column)) {
            return null;
        }

        $value = $patient->{$column};

        if (! $this->filled($value)) {
            return null;
        }

        if ($column === 'jk') {
            return match (strtoupper(trim((string) $value))) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => trim((string) $value),
            };
        }

        if (in_array($column, ['tgl_lahir', 'tgl_daftar'], true)) {
            return $this->dateValue($value) ?? trim((string) $value);
        }

        return trim((string) $value);
    }

    private function dateValue(mixed $value): ?string
    {
        $date = trim((string) $value);

        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (Throwable) {
            return null;
        }
    }

    private function filled(mixed $value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && $value !== '-' && ! str_starts_with($value, '0000-00-00');
    }
}
