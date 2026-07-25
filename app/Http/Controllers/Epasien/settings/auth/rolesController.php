<?php

namespace App\Http\Controllers\Epasien\settings\auth;

use App\Http\Controllers\Controller;
use App\Services\epasien\settings\auth\rolesService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class rolesController extends Controller
{
    protected $rolesService;

    public function __construct(rolesService $rolesService)
    {
        $this->rolesService = $rolesService;
    }

    public function roles()
    {
        return view('e-pasien.settings.auth.roles.roles');
    }

    public function table(Request $request)
    {
        $roles = $this->rolesService->getData();
        $stats = [
            'total' => $roles->count(),
            'system' => $roles->filter(fn ($role) => $this->isProtectedRole($role))->count(),
            'with_permissions' => $roles->filter(fn ($role) => $role->permissions_count > 0)->count(),
            'assignments' => $roles->sum('permissions_count'),
        ];
        $filteredRoles = $roles;

        if ($request->filled('type')) {
            $filteredRoles = $filteredRoles->filter(function ($role) use ($request) {
                return ($this->isProtectedRole($role) ? 'system' : 'custom') === $request->input('type');
            });
        }

        return DataTables::of($filteredRoles)
            ->addIndexColumn()
            ->editColumn('name', function ($role) {
                $tone = $this->isProtectedRole($role) ? 'orange' : 'purple';

                return '
                    <div class="access-person">
                        <span class="access-avatar '.$tone.'"><i class="bi bi-shield-lock"></i></span>
                        <span>
                            <strong>'.e($role->name).'</strong>
                            <small>'.($this->isProtectedRole($role) ? 'System role' : 'Custom role').'</small>
                        </span>
                    </div>
                ';
            })
            ->editColumn('guard_name', function ($role) {
                return '<span class="access-code">'.e($role->guard_name).'</span>';
            })
            ->addColumn('type', function ($role) {
                return $this->isProtectedRole($role) ? 'system' : 'custom';
            })
            ->addColumn('permissions', function ($role) {
                if ($role->permissions->isEmpty()) {
                    return '<span class="access-badge gray">Belum ada permission</span>';
                }

                return '<div class="access-role-stack">'
                    .$role->permissions
                        ->take(6)
                        ->map(function ($permission) {
                            return '<span class="access-badge green"><i class="bi bi-key"></i>'.e($permission->name).'</span>';
                        })
                        ->implode(' ')
                    .($role->permissions_count > 6
                        ? ' <span class="access-badge gray">+'.($role->permissions_count - 6).'</span>'
                        : '')
                    .'</div>';
            })
            ->addColumn('actions', function ($role) {
                $deleteButton = $this->isProtectedRole($role)
                    ? '<button type="button" class="access-icon-button disabled" title="Role inti tidak dapat dihapus" disabled><i class="bi bi-trash"></i></button>'
                    : '<button type="button" class="access-icon-button danger" title="Hapus role" onclick="deleteRoles('.$role->id.')"><i class="bi bi-trash"></i></button>';

                return '
                    <div class="access-actions">
                        <button type="button" class="access-icon-button" title="Edit role" onclick="editRoles('.$role->id.')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        '.$deleteButton.'
                        <button type="button" class="access-icon-button warning" title="Assign permission" onclick="assignPermissions('.$role->id.')">
                            <i class="bi bi-shield-lock"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['name', 'guard_name', 'permissions', 'actions'])
            ->with(['stats' => $stats])
            ->make(true);
    }

    public function store(Request $request)
    {
        $guardName = 'web';

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where(fn ($query) => $query->where('guard_name', $guardName)),
            ],
        ]);

        $validated['guard_name'] = $guardName;
        $role = $this->rolesService->createRoles($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil dibuat.',
            'data' => $role,
        ], 201);
    }

    public function edit(string $id)
    {
        $role = $this->rolesService->findRole($id);

        if (! $role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        return response()->json($role);
    }

    public function update(Request $request, string $id)
    {
        $role = $this->rolesService->findRole($id);

        if (! $role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        $guardName = $role->guard_name;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('guard_name', $guardName)),
            ],
        ]);

        if ($this->isProtectedRole($role) && $validated['name'] !== $role->name) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role inti tidak dapat diganti namanya.',
            ], 422);
        }

        $validated['guard_name'] = $guardName;
        $updatedRole = $this->rolesService->update($id, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil diperbarui.',
            'data' => $updatedRole,
        ]);
    }

    public function destroy(string $id)
    {
        $role = $this->rolesService->findRole($id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        if ($this->isProtectedRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'Role inti tidak dapat dihapus.',
            ], 422);
        }

        $this->rolesService->destroy($id);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus.',
        ]);
    }

    public function dataPermissions()
    {
        return response()->json($this->rolesService->getPermissions());
    }

    public function assignPermissions(Request $request)
    {
        $validated = $request->validate([
            'roleId' => ['required', 'exists:roles,id'],
            'permissions_id' => ['nullable', 'array'],
            'permissions_id.*' => ['exists:permissions,id'],
        ]);

        $result = $this->rolesService->assignPermissionsToRole(
            $validated['roleId'],
            $validated['permissions_id'] ?? []
        );

        if (! $result) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permission role berhasil diperbarui.',
        ]);
    }

    public function getRolePermissions($roleId)
    {
        $permissions = $this->rolesService->getPermissionsByRole($roleId);

        if (! $permissions) {
            return response()->json([
                'status' => false,
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $permissions->pluck('id'),
        ]);
    }

    private function isProtectedRole($role): bool
    {
        return $role->name === config('access-control.super_admin_role');
    }
}
