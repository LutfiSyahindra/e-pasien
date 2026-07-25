<?php

namespace App\Http\Controllers\Epasien\settings\auth;

use App\Http\Controllers\Controller;
use App\Jobs\SyncPatientUsersJob;
use App\Models\User;
use App\Services\epasien\settings\auth\PatientUserSyncService;
use App\Services\epasien\settings\auth\rolesService;
use App\Services\epasien\settings\auth\usersService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;

class usersController extends Controller
{
    protected $userService;

    protected $rolesService;

    protected $patientUserSyncService;

    public function __construct(
        usersService $userService,
        rolesService $rolesService,
        PatientUserSyncService $patientUserSyncService
    ) {
        $this->userService = $userService;
        $this->rolesService = $rolesService;
        $this->patientUserSyncService = $patientUserSyncService;
    }

    /**
     * Display a listing of the resource.
     */
    public function users()
    {
        return view('e-pasien.settings.auth.users.users');
    }

    public function table(Request $request)
    {
        $query = $this->userService->queryWithRoles();

        if ($request->filled('status')) {
            $query->where('status', filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN));
        }

        return app(DataTables::class)->eloquent($query)
            ->filter(function ($query) use ($request): void {
                $search = trim((string) $request->input('search.value'));

                if ($search === '') {
                    return;
                }

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->addIndexColumn()
            ->editColumn('name', function (User $user) {
                $username = $user->username ?: '-';

                return '
                    <div class="access-person">
                        <span class="access-avatar">'.e($this->initials($user->name)).'</span>
                        <span>
                            <strong>'.e($user->name).'</strong>
                            <small>Username: '.e($username).'</small>
                        </span>
                    </div>
                ';
            })
            ->editColumn('email', fn (User $user): string => '<span class="access-code">'.e($user->email).'</span>')
            ->addColumn('roles', function (User $user): string {
                return $user->roles->pluck('name')->map(function (string $roleName) {
                    return '<span class="access-badge purple"><i class="bi bi-person-badge"></i>'.e($roleName).'</span>';
                })->implode(' ') ?: '<span class="access-badge gray">Belum ada role</span>';
            })
            ->addColumn('actions', function (User $user) {
                return '
                    <div class="access-actions">
                        <button type="button" class="access-icon-button" title="Edit user" onclick="editUsers('.$user->id.')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="access-icon-button danger" title="Hapus user" onclick="deleteUsers('.$user->id.')">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button" class="access-icon-button warning" title="Assign role" onclick="assignRoles('.$user->id.')">
                            <i class="bi bi-shield-lock"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['name', 'email', 'roles', 'actions'])
            ->with(['stats' => $this->userService->getStats()])
            ->make(true);

    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:users,id'],
            'status' => ['required', 'boolean'],
        ]);

        if ((int) $validated['id'] === (int) $request->user()->id && ! (bool) $validated['status']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.',
            ], 422);
        }

        $user = $this->userService->updateStatus($validated['id'], $validated['status']);

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status user berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge([
            'username' => filled($request->input('username'))
                ? trim((string) $request->input('username'))
                : null,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $validated['username'] = filled($validated['username'] ?? null)
            ? trim((string) $validated['username'])
            : null;
        $validated['status'] = true;
        $user = $this->userService->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dibuat',
            'data' => $user,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $dataUsers = $this->userService->findById($id);

        if (! $dataUsers) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        return response()->json($dataUsers);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->merge([
            'username' => filled($request->input('username'))
                ? trim((string) $request->input('username'))
                : null,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100', Rule::unique('users', 'username')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $validated['username'] = filled($validated['username'] ?? null)
            ? trim((string) $validated['username'])
            : null;

        $user = $this->userService->update($id, $validated);

        if (! $user) {
            return response()->json([
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil diperbarui.',
            'data' => $user,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            if (! $this->userService->destroy($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            // Tangani jika terjadi kesalahan
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

    public function dataRoles()
    {
        $Roles = $this->rolesService->getRoles();

        return response()->json($Roles);
    }

    public function syncPasienUsers(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        if ($this->patientUserSyncService->hasActiveSync()) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Sync users pasien masih berjalan.',
                'sync' => $this->patientUserSyncService->status(),
            ], 409);
        }

        $role = Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($validated['role_id']);

        $status = $this->patientUserSyncService->markQueued($request->user()?->id, $role);

        $job = new SyncPatientUsersJob((int) $role->id);

        if (config('queue.default') === 'sync') {
            $job->onConnection('database');
        }

        dispatch($job);

        return response()->json([
            'status' => 'success',
            'message' => 'Sync users pasien sudah masuk antrean.',
            'sync' => $status,
        ], 202);
    }

    public function syncPasienUsersStatus()
    {
        return response()->json([
            'status' => 'success',
            'sync' => $this->patientUserSyncService->status(),
        ]);
    }

    public function stopSyncPasienUsers(Request $request)
    {
        if (! $this->patientUserSyncService->hasActiveSync()) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Tidak ada sync users pasien yang sedang berjalan.',
                'sync' => $this->patientUserSyncService->status(),
            ], 409);
        }

        $status = $this->patientUserSyncService->requestStop($request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Permintaan stop sync sudah dikirim.',
            'sync' => $status,
        ]);
    }

    public function getBranches()
    {
        return response()->json([]);
    }

    public function assignRoles(Request $request)
    {
        $validated = $request->validate([
            'userssId' => 'required|exists:users,id',
            'roles_id' => 'nullable|array',
            'roles_id.*' => 'exists:roles,id',
        ]);

        $this->userService->assignRolesToUsers(
            $validated['userssId'],
            $validated['roles_id'] ?? []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Role user berhasil diperbarui.',
        ]);
    }

    public function getUserRoles($userId)
    {
        $Roles = $this->userService->getUsersRoles($userId);

        return response()->json([
            'status' => true,
            'data' => $Roles->pluck('id'), // hanya kirim array ID
        ]);
    }

    private function initials(string $name): string
    {
        return Str::of($name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('') ?: 'U';
    }
}
