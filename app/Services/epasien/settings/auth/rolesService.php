<?php

namespace App\Services\epasien\settings\auth;

use App\Repositories\epasien\settings\auth\rolesRepository;

class rolesService
{
    protected $RolesRepository;

    public function __construct(rolesRepository $rolesRepository)
    {
        $this->RolesRepository = $rolesRepository;
    }

    /**
     * Create a new class instance.
     */
    public function getData()
    {
        return $this->RolesRepository->getData();
    }

    public function getRoles()
    {
        $Roles = $this->RolesRepository->getRoles();

        $dataRoles = [];
        foreach ($Roles as $r) {
            $dataRoles[] = [
                'id' => $r->id,
                'name' => $r->name,
            ];
        }

        return $dataRoles;
    }

    public function getPermissions()
    {
        $permissions = $this->RolesRepository->getPermissions();

        $dataPermissions = [];
        foreach ($permissions as $permission) {
            $dataPermissions[] = [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        }

        return $dataPermissions;
    }

    public function createRoles($data)
    {
        return $this->RolesRepository->createRoles($data);
    }

    public function findRole($id)
    {
        return $this->RolesRepository->findRole($id);
    }

    public function update($id, $data)
    {
        $Roles = $this->RolesRepository->findRole($id);

        if (! $Roles) {
            return null; // atau bisa lempar exception
        }

        $Roles->update($data);

        return $Roles;
    }

    public function destroy($id)
    {
        $Roles = $this->RolesRepository->findRole($id);

        if (! $Roles) {
            return false;
        }

        $Roles->delete();

        return true;
    }

    public function assignPermissionsToRole($roleId, array $permissionIds)
    {
        $role = $this->RolesRepository->findRole($roleId);

        if (! $role) {
            return null;
        }

        $permissions = $this->RolesRepository->getPermissionsByIds($permissionIds);

        return $this->RolesRepository->syncPermissions($role, $permissions);
    }

    public function getPermissionsByRole($roleId)
    {
        return $this->RolesRepository->getRolePermissions($roleId);
    }
}
