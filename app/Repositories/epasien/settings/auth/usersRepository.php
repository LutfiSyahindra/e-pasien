<?php

namespace App\Repositories\epasien\settings\auth;

use App\Models\User;
use Spatie\Permission\Models\Role;

class usersRepository
{
    public function getData()
    {
        return User::with('roles:id,name')
            ->latest()
            ->get();
    }

    public function queryWithRoles()
    {
        return User::query()
            ->with('roles:id,name')
            ->select('users.*')
            ->latest();
    }

    public function getStats(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->where('status', true)->count(),
            'inactive' => User::query()->where('status', false)->count(),
            'with_roles' => User::query()->whereHas('roles')->count(),
        ];
    }

    public function updateStatus($id, $status)
    {
        $user = User::find($id);

        if (! $user) {
            return null;
        }

        $user->status = (bool) $status;
        $user->save();

        return $user;
    }

    public function createUser($data)
    {
        return User::create($data)->load('roles:id,name');
    }

    public function findById($id)
    {
        return User::with('roles:id,name')->find($id);
    }

    public function getRolesByIds(array $ids)
    {
        return Role::whereIn('id', $ids)->get();
    }

    public function syncRoles($user, array $roles)
    {
        return $user->syncRoles($roles);
    }

    public function getUsersRoles($userId)
    {
        $user = User::with('roles')->findOrFail($userId);

        return $user->roles;
    }
}
