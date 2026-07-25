<?php

namespace App\Repositories\epasien\settings\auth;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class permissionsRepository
{
    public function getData()
    {
        return Permission::with('roles:id,name')
            ->withCount('roles')
            ->orderBy('name')
            ->get();
    }

    public function createPermission(array $data)
    {
        $permission = Permission::create($data);
        $this->clearPermissionCache();

        return $permission->load('roles:id,name');
    }

    public function findById($id)
    {
        return Permission::with('roles:id,name')->find($id);
    }

    public function updatePermission($permission, array $data)
    {
        $permission->update($data);
        $this->clearPermissionCache();

        return $permission->fresh('roles:id,name');
    }

    public function destroyPermission($permission)
    {
        $permission->delete();
        $this->clearPermissionCache();

        return true;
    }

    private function clearPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
