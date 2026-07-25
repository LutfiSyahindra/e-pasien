<?php

namespace App\Services\epasien\settings\auth;

use App\Repositories\epasien\settings\auth\permissionsRepository;

class permissionsService
{
    protected $permissionsRepository;

    public function __construct(permissionsRepository $permissionsRepository)
    {
        $this->permissionsRepository = $permissionsRepository;
    }

    public function getData()
    {
        return $this->permissionsRepository->getData();
    }

    public function create(array $data)
    {
        return $this->permissionsRepository->createPermission($data);
    }

    public function findById($id)
    {
        return $this->permissionsRepository->findById($id);
    }

    public function update($id, array $data)
    {
        $permission = $this->permissionsRepository->findById($id);

        if (! $permission) {
            return null;
        }

        return $this->permissionsRepository->updatePermission($permission, $data);
    }

    public function destroy($id)
    {
        $permission = $this->permissionsRepository->findById($id);

        if (! $permission) {
            return false;
        }

        return $this->permissionsRepository->destroyPermission($permission);
    }
}
