<?php

namespace App\Repositories\epasien\settings\auth;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class rolesRepository
{
    /**
     * Create a new class instance.
     */
    public function getData()
    {
        return Role::with('permissions:id,name')
            ->withCount('permissions')
            ->orderBy('name')
            ->get();
    }

    public function getRoles()
    {
        return Role::query()
            ->orderBy('name')
            ->get();
    }

    public function createRoles($data)
    {
        return Role::create($data);
    }

    public function findRole($id)
    {
        return Role::with('permissions:id,name')->find($id);
    }

    public function getPermissions()
    {
        return Permission::query()
            ->orderBy('name')
            ->get();
    }

    public function getPermissionsByIds(array $ids)
    {
        return Permission::whereIn('id', $ids)->get();
    }

    public function syncPermissions($role, $permissions)
    {
        return $role->syncPermissions($permissions);
    }

    public function getRolePermissions($roleId)
    {
        $role = Role::with('permissions')->find($roleId);

        return $role?->permissions;
    }
}
