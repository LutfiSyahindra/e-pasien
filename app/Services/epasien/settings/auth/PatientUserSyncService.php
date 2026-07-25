<?php

namespace App\Services\epasien\settings\auth;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class PatientUserSyncService
{
    private const CHUNK_SIZE = 1000;

    private const STATUS_CACHE_KEY = 'e_pasien.patient_user_sync.status';

    public function status(): array
    {
        $status = $this->rawStatus();

        if (($status['state'] ?? null) === 'stopping' && ! $this->hasPendingSyncJob()) {
            return $this->markStopped($status['summary'] ?? []);
        }

        return $status;
    }

    private function rawStatus(): array
    {
        return Cache::get(self::STATUS_CACHE_KEY, [
            'state' => 'idle',
            'message' => 'Belum ada sync berjalan.',
            'summary' => $this->emptySummary(),
        ]);
    }

    public function hasActiveSync(): bool
    {
        $status = $this->status();

        if (! in_array($status['state'] ?? 'idle', ['queued', 'running', 'stopping'], true)) {
            return false;
        }

        $updatedAt = isset($status['updated_at'])
            ? Carbon::parse($status['updated_at'])
            : null;

        return $updatedAt !== null && $updatedAt->gt(now()->subHours(2));
    }

    public function markQueued(?int $userId = null, ?Role $role = null): array
    {
        return $this->putStatus([
            'state' => 'queued',
            'message' => 'Sync users pasien masuk antrean.',
            'requested_by' => $userId,
            'role' => $this->roleStatus($role),
            'started_at' => null,
            'finished_at' => null,
            'stop_requested' => false,
            'stop_requested_by' => null,
            'stop_requested_at' => null,
            'summary' => $this->emptySummary(),
        ]);
    }

    public function markRunning(array $summary = []): array
    {
        $status = $this->rawStatus();
        $stopRequested = (bool) ($status['stop_requested'] ?? false);

        return $this->putStatus([
            'state' => $stopRequested ? 'stopping' : 'running',
            'message' => $stopRequested
                ? 'Permintaan stop sync diterima. Menunggu chunk aktif selesai.'
                : 'Sync users pasien sedang diproses.',
            'requested_by' => $status['requested_by'] ?? null,
            'role' => $status['role'] ?? null,
            'started_at' => $status['started_at'] ?? now()->toDateTimeString(),
            'finished_at' => null,
            'stop_requested' => $stopRequested,
            'stop_requested_by' => $status['stop_requested_by'] ?? null,
            'stop_requested_at' => $status['stop_requested_at'] ?? null,
            'summary' => array_merge($this->emptySummary(), $status['summary'] ?? [], $summary),
        ]);
    }

    public function requestStop(?int $userId = null): array
    {
        $status = $this->rawStatus();

        return $this->putStatus([
            'state' => 'stopping',
            'message' => 'Permintaan stop sync diterima. Menunggu chunk aktif selesai.',
            'requested_by' => $status['requested_by'] ?? null,
            'role' => $status['role'] ?? null,
            'started_at' => $status['started_at'] ?? null,
            'finished_at' => null,
            'stop_requested' => true,
            'stop_requested_by' => $userId,
            'stop_requested_at' => now()->toDateTimeString(),
            'summary' => array_merge($this->emptySummary(), $status['summary'] ?? []),
        ]);
    }

    public function markCompleted(array $summary): array
    {
        $status = $this->rawStatus();

        return $this->putStatus([
            'state' => 'completed',
            'message' => 'Sync users pasien selesai.',
            'requested_by' => $status['requested_by'] ?? null,
            'role' => $status['role'] ?? null,
            'started_at' => $status['started_at'] ?? null,
            'finished_at' => now()->toDateTimeString(),
            'stop_requested' => false,
            'stop_requested_by' => $status['stop_requested_by'] ?? null,
            'stop_requested_at' => $status['stop_requested_at'] ?? null,
            'summary' => array_merge($this->emptySummary(), $summary),
        ]);
    }

    public function markStopped(array $summary): array
    {
        $status = $this->rawStatus();

        return $this->putStatus([
            'state' => 'stopped',
            'message' => 'Sync users pasien dihentikan.',
            'requested_by' => $status['requested_by'] ?? null,
            'role' => $status['role'] ?? null,
            'started_at' => $status['started_at'] ?? null,
            'finished_at' => now()->toDateTimeString(),
            'stop_requested' => false,
            'stop_requested_by' => $status['stop_requested_by'] ?? null,
            'stop_requested_at' => $status['stop_requested_at'] ?? null,
            'summary' => array_merge($this->emptySummary(), $summary, ['stopped' => true]),
        ]);
    }

    public function markFailed(string $message): array
    {
        $status = $this->rawStatus();

        return $this->putStatus([
            'state' => 'failed',
            'message' => $message,
            'requested_by' => $status['requested_by'] ?? null,
            'role' => $status['role'] ?? null,
            'started_at' => $status['started_at'] ?? null,
            'finished_at' => now()->toDateTimeString(),
            'stop_requested' => false,
            'stop_requested_by' => $status['stop_requested_by'] ?? null,
            'stop_requested_at' => $status['stop_requested_at'] ?? null,
            'summary' => array_merge($this->emptySummary(), $status['summary'] ?? []),
        ]);
    }

    public function sync(int $roleId, ?callable $progress = null): array
    {
        $summary = $this->emptySummary();
        $summary['total'] = $this->patientSourceQuery()->count();

        if ($progress !== null) {
            $progress($summary);
        }

        $role = $this->roleById($roleId);
        // Hash password tanggal lahir YYYYMMDD dibuat saat login pertama agar sync tetap ringan.
        $placeholderPassword = Hash::make(Str::random(48));

        if ($this->shouldStop()) {
            $summary['stopped'] = true;

            return $summary;
        }

        $this->patientSourceQuery()
            ->orderBy('no_rkm_medis')
            ->chunk(self::CHUNK_SIZE, function ($patients) use (&$summary, $role, $placeholderPassword, $progress): bool {
                if ($this->shouldStop()) {
                    $summary['stopped'] = true;

                    return false;
                }

                $preparedPatients = collect($patients)
                    ->map(fn (object $patient): ?array => $this->preparePatient($patient))
                    ->filter();

                $invalidCount = $patients->count() - $preparedPatients->count();
                $uniquePatients = $preparedPatients
                    ->unique('username')
                    ->values();
                $passwordForNewPatients = $placeholderPassword;

                DB::transaction(function () use ($uniquePatients, $role, $passwordForNewPatients, &$summary): void {
                    $usernames = $uniquePatients->pluck('username')->all();

                    if ($usernames === []) {
                        return;
                    }

                    $existingUsernames = User::query()
                        ->whereIn('username', $usernames)
                        ->pluck('username')
                        ->all();
                    $now = now();

                    $patientRows = $uniquePatients
                        ->map(function (array $patient) use ($now, $passwordForNewPatients): array {
                            return [
                                'name' => $patient['name'],
                                'username' => $patient['username'],
                                'email' => $this->patientEmail($patient['username']),
                                'email_verified_at' => $now,
                                'password' => $passwordForNewPatients,
                                'status' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        })
                        ->values()
                        ->all();

                    if ($patientRows !== []) {
                        User::query()->upsert($patientRows, ['username'], ['name', 'updated_at']);
                    }

                    $insertedRows = count($usernames) - count($existingUsernames);

                    $userIds = User::query()
                        ->whereIn('username', $usernames)
                        ->pluck('id')
                        ->map(fn ($id): int => (int) $id)
                        ->all();

                    $attachedRoles = $this->attachPatientRole($role->id, $userIds);

                    $summary['inserted'] += $insertedRows;
                    $summary['existing'] += count($usernames) - $insertedRows;
                    $summary['role_attached'] += $attachedRoles;
                });

                $summary['processed'] += $patients->count();
                $summary['skipped_invalid'] += $invalidCount;

                if ($progress !== null) {
                    $progress($summary);
                }

                if ($this->shouldStop()) {
                    $summary['stopped'] = true;

                    return false;
                }

                return true;
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $summary;
    }

    public function provisionFromPatientCredentials(string $username, string $password): ?User
    {
        $username = trim($username);
        $password = trim($password);

        if ($username === '' || $password === '') {
            return null;
        }

        $patient = $this->patientSourceQuery()
            ->where('no_rkm_medis', $username)
            ->first();

        if (! $patient) {
            return null;
        }

        $preparedPatient = $this->preparePatient($patient);

        if (! $preparedPatient || ! hash_equals($preparedPatient['password'], $password)) {
            return null;
        }

        $role = $this->patientRole();

        return DB::transaction(function () use ($preparedPatient, $role, $password): User {
            $user = User::query()
                ->where('username', $preparedPatient['username'])
                ->first();

            if (! $user) {
                $user = User::query()->create([
                    'name' => $preparedPatient['name'],
                    'username' => $preparedPatient['username'],
                    'email' => $this->patientEmail($preparedPatient['username']),
                    'email_verified_at' => now(),
                    'password' => $password,
                    'status' => true,
                ]);
            } else {
                $updates = [];

                if ($user->name !== $preparedPatient['name']) {
                    $updates['name'] = $preparedPatient['name'];
                }

                if (! Hash::check($password, $user->password)) {
                    $updates['password'] = Hash::make($password);
                }

                if ($updates !== []) {
                    $user->forceFill($updates)->save();
                }
            }

            if (! $user->hasRole($role->name)) {
                $user->assignRole($role);
            }

            return $user;
        });
    }

    private function patientSourceQuery(): Builder
    {
        return DB::connection('mysql_khanza')
            ->table('pasien')
            ->select('no_rkm_medis', 'nm_pasien', 'tgl_lahir')
            ->whereNotNull('no_rkm_medis')
            ->where('no_rkm_medis', '!=', '')
            ->whereNotNull('tgl_lahir');
    }

    private function preparePatient(object $patient): ?array
    {
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? ''));
        $birthDate = $this->normalizeBirthDate($patient->tgl_lahir ?? null);

        if ($medicalRecordNumber === '' || $birthDate === null) {
            return null;
        }

        return [
            'name' => $this->patientName($medicalRecordNumber, $patient->nm_pasien ?? null),
            'username' => $medicalRecordNumber,
            'password' => $birthDate,
        ];
    }

    private function normalizeBirthDate(mixed $birthDate): ?string
    {
        if ($birthDate instanceof DateTimeInterface) {
            return Carbon::instance($birthDate)->format('Ymd');
        }

        $value = trim((string) $birthDate);

        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Ymd');
        } catch (Throwable) {
            return str_replace('-', '', $value);
        }
    }

    private function patientRole(): Role
    {
        return Role::findOrCreate(config('access-control.patient_role', 'Patient'), 'web');
    }

    private function roleById(int $roleId): Role
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($roleId);
    }

    private function roleStatus(?Role $role): ?array
    {
        if (! $role) {
            return null;
        }

        return [
            'id' => $role->id,
            'name' => $role->name,
        ];
    }

    private function patientName(string $medicalRecordNumber, mixed $patientName = null): string
    {
        $patientName = trim((string) $patientName);

        if ($patientName !== '') {
            return $patientName;
        }

        return 'Pasien '.$medicalRecordNumber;
    }

    private function shouldStop(): bool
    {
        $status = $this->rawStatus();

        return (bool) ($status['stop_requested'] ?? false)
            || ($status['state'] ?? null) === 'stopping';
    }

    private function hasPendingSyncJob(): bool
    {
        if (config('queue.default') !== 'database') {
            return true;
        }

        $connection = config('queue.connections.database.connection') ?: config('database.default');
        $table = config('queue.connections.database.table', 'jobs');

        return DB::connection($connection)
            ->table($table)
            ->where('payload', 'like', '%SyncPatientUsersJob%')
            ->exists();
    }

    private function patientEmail(string $medicalRecordNumber): string
    {
        $safeMedicalRecordNumber = Str::of($medicalRecordNumber)
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '.')
            ->trim('.-_')
            ->limit(70, '')
            ->value();

        if ($safeMedicalRecordNumber === '') {
            $safeMedicalRecordNumber = 'rm';
        }

        return sprintf(
            'pasien-%s-%s@e-pasien.local',
            $safeMedicalRecordNumber,
            substr(sha1($medicalRecordNumber), 0, 12)
        );
    }

    private function attachPatientRole(int $roleId, array $userIds): int
    {
        if ($userIds === []) {
            return 0;
        }

        $pivotTable = config('permission.table_names.model_has_roles');
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $modelKey = config('permission.column_names.model_morph_key') ?? 'model_id';
        $modelType = User::class;

        $existingUserIds = DB::table($pivotTable)
            ->where($rolePivotKey, $roleId)
            ->where('model_type', $modelType)
            ->whereIn($modelKey, $userIds)
            ->pluck($modelKey)
            ->map(fn ($id): int => (int) $id)
            ->all();
        $existingLookup = array_flip($existingUserIds);

        $rows = collect($userIds)
            ->reject(fn (int $userId): bool => isset($existingLookup[$userId]))
            ->map(fn (int $userId): array => [
                $rolePivotKey => $roleId,
                'model_type' => $modelType,
                $modelKey => $userId,
            ])
            ->values()
            ->all();

        if ($rows === []) {
            return 0;
        }

        DB::table($pivotTable)->insertOrIgnore($rows);

        return count($rows);
    }

    private function putStatus(array $status): array
    {
        $status['updated_at'] = now()->toDateTimeString();

        Cache::put(self::STATUS_CACHE_KEY, $status, now()->addHours(12));

        return $status;
    }

    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'processed' => 0,
            'inserted' => 0,
            'existing' => 0,
            'role_attached' => 0,
            'skipped_invalid' => 0,
            'stopped' => false,
        ];
    }
}
