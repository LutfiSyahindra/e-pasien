<?php

namespace App\Http\Controllers\Epasien\settings\auth;

use App\Http\Controllers\Controller;
use App\Services\epasien\settings\auth\permissionsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class permissionsController extends Controller
{
    protected $permissionsService;

    public function __construct(permissionsService $permissionsService)
    {
        $this->permissionsService = $permissionsService;
    }

    public function permissions()
    {
        return view('e-pasien.settings.auth.permissions.permissions');
    }

    public function table(Request $request)
    {
        $permissions = $this->permissionsService->getData();
        $stats = [
            'total' => $permissions->count(),
            'protected' => $permissions->filter(fn ($permission) => $this->isProtectedPermission($permission))->count(),
            'assigned' => $permissions->filter(fn ($permission) => $permission->roles_count > 0)->count(),
            'unassigned' => $permissions->filter(fn ($permission) => $permission->roles_count === 0)->count(),
        ];
        $filteredPermissions = $permissions;

        if ($request->filled('type')) {
            $filteredPermissions = $filteredPermissions->filter(function ($permission) use ($request) {
                return ($this->isProtectedPermission($permission) ? 'protected' : 'custom') === $request->input('type');
            });
        }

        return DataTables::of($filteredPermissions)
            ->addIndexColumn()
            ->editColumn('name', function ($permission) {
                $tone = $this->isProtectedPermission($permission) ? 'green' : 'cyan';

                return '
                    <div class="access-person">
                        <span class="access-avatar '.$tone.'"><i class="bi bi-key"></i></span>
                        <span>
                            <strong>'.e($permission->name).'</strong>
                            <small>'.($this->isProtectedPermission($permission) ? 'Protected permission' : 'Custom permission').'</small>
                        </span>
                    </div>
                ';
            })
            ->editColumn('guard_name', function ($permission) {
                return '<span class="access-code">'.e($permission->guard_name).'</span>';
            })
            ->addColumn('type', function ($permission) {
                return $this->isProtectedPermission($permission) ? 'protected' : 'custom';
            })
            ->addColumn('roles', function ($permission) {
                if ($permission->roles->isEmpty()) {
                    return '<span class="access-badge gray">Belum dipakai role</span>';
                }

                return '<div class="access-role-stack">'
                    .$permission->roles
                        ->take(6)
                        ->map(function ($role) {
                            return '<span class="access-badge purple"><i class="bi bi-person-badge"></i>'.e($role->name).'</span>';
                        })
                        ->implode(' ')
                    .($permission->roles_count > 6
                        ? ' <span class="access-badge gray">+'.($permission->roles_count - 6).'</span>'
                        : '')
                    .'</div>';
            })
            ->addColumn('actions', function ($permission) {
                $isProtected = $this->isProtectedPermission($permission);

                $editButton = $isProtected
                    ? '<button type="button" class="access-icon-button disabled" title="Permission inti tidak dapat diedit" disabled><i class="bi bi-pencil-square"></i></button>'
                    : '<button type="button" class="access-icon-button" title="Edit permission" onclick="editPermissions('.$permission->id.')"><i class="bi bi-pencil-square"></i></button>';

                $deleteButton = $isProtected
                    ? '<button type="button" class="access-icon-button disabled" title="Permission inti tidak dapat dihapus" disabled><i class="bi bi-trash"></i></button>'
                    : '<button type="button" class="access-icon-button danger" title="Hapus permission" onclick="deletePermissions('.$permission->id.')"><i class="bi bi-trash"></i></button>';

                return '<div class="access-actions">'.$editButton.' '.$deleteButton.'</div>';
            })
            ->rawColumns(['name', 'guard_name', 'roles', 'actions'])
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
                Rule::unique('permissions', 'name')->where(fn ($query) => $query->where('guard_name', $guardName)),
            ],
        ]);

        $validated['guard_name'] = $guardName;
        $permission = $this->permissionsService->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission berhasil dibuat.',
            'data' => $permission,
        ], 201);
    }

    public function edit(string $id)
    {
        $permission = $this->permissionsService->findById($id);

        if (! $permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        if ($this->isProtectedPermission($permission)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission inti tidak dapat diedit.',
            ], 422);
        }

        return response()->json($permission);
    }

    public function update(Request $request, string $id)
    {
        $permission = $this->permissionsService->findById($id);

        if (! $permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        if ($this->isProtectedPermission($permission)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission inti tidak dapat diedit.',
            ], 422);
        }

        $guardName = $permission->guard_name;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('permissions', 'name')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('guard_name', $guardName)),
            ],
        ]);

        $validated['guard_name'] = $guardName;
        $updatedPermission = $this->permissionsService->update($id, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission berhasil diperbarui.',
            'data' => $updatedPermission,
        ]);
    }

    public function destroy(string $id)
    {
        $permission = $this->permissionsService->findById($id);

        if (! $permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        if ($this->isProtectedPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission inti tidak dapat dihapus.',
            ], 422);
        }

        $this->permissionsService->destroy($id);

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil dihapus.',
        ]);
    }

    private function isProtectedPermission($permission): bool
    {
        return in_array($permission->name, config('access-control.protected_permissions', []), true);
    }
}
