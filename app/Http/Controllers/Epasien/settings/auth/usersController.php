<?php

namespace App\Http\Controllers\Epasien\settings\auth;

use App\Http\Controllers\Controller;
use App\Services\epasien\settings\auth\rolesService;
use App\Services\epasien\settings\auth\usersService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class usersController extends Controller
{
    protected $userService;

    protected $rolesService;

    public function __construct(usersService $userService, rolesService $rolesService)
    {
        $this->userService = $userService;
        $this->rolesService = $rolesService;
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
        $Users = $this->userService->getData();
        $stats = [
            'total' => $Users->count(),
            'active' => $Users->where('status', true)->count(),
            'inactive' => $Users->where('status', false)->count(),
            'with_roles' => $Users->filter(fn ($user) => $user->roles->isNotEmpty())->count(),
        ];
        $filteredUsers = $Users;

        if ($request->filled('status')) {
            $filteredUsers = $filteredUsers->where(
                'status',
                filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        $dataUsers = [];
        foreach ($filteredUsers as $r) {
            $dataUsers[] = [
                'id' => $r->id,
                'name' => '
                    <div class="access-person">
                        <span class="access-avatar">'.e($this->initials($r->name)).'</span>
                        <span>
                            <strong>'.e($r->name).'</strong>
                            <small>User ID #'.e((string) $r->id).'</small>
                        </span>
                    </div>
                ',
                'email' => '<span class="access-code">'.e($r->email).'</span>',
                'roles' => $r->roles->pluck('name')->map(function (string $roleName) {
                    return '<span class="access-badge purple"><i class="bi bi-person-badge"></i>'.e($roleName).'</span>';
                })->implode(' ') ?: '<span class="access-badge gray">Belum ada role</span>',
                'status' => $r->status,
            ];
        }

        return DataTables::of($dataUsers)
            ->addIndexColumn()
            ->addColumn('actions', function ($dataUsers) {
                return '
                    <div class="access-actions">
                        <button type="button" class="access-icon-button" title="Edit user" onclick="editUsers('.$dataUsers['id'].')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="access-icon-button danger" title="Hapus user" onclick="deleteUsers('.$dataUsers['id'].')">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button" class="access-icon-button warning" title="Assign role" onclick="assignRoles('.$dataUsers['id'].')">
                            <i class="bi bi-shield-lock"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['name', 'email', 'roles', 'actions'])
            ->with(['stats' => $stats])
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

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
