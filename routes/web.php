<?php

use App\Http\Controllers\Epasien\settings\auth\permissionsController;
use App\Http\Controllers\Epasien\settings\auth\rolesController;
use App\Http\Controllers\Epasien\settings\auth\usersController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landingPage.landingPage');
});

Route::get('/dashboard', function () {
    return view('e-pasien.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('e-pasien/settings')->group(function () {
        // Users
        Route::get('/auth/users', [usersController::class, 'users'])->name('users.users');
        Route::get('/users/tableUsers', [usersController::class, 'table'])->name('users.table');
        Route::put('/users/updateStatus', [usersController::class, 'updateStatus'])->name('users.updateStatus');
        Route::get('/users/getBranches', [usersController::class, 'getBranches'])->name('users.getBranches');
        Route::post('/users/sync-pasien', [usersController::class, 'syncPasienUsers'])->name('users.syncPasien');
        Route::post('/users/sync-pasien/stop', [usersController::class, 'stopSyncPasienUsers'])->name('users.stopSyncPasien');
        Route::get('/users/sync-pasien/status', [usersController::class, 'syncPasienUsersStatus'])->name('users.syncPasienStatus');
        Route::post('/users/store', [usersController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [usersController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}/update', [usersController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}/delete', [usersController::class, 'destroy'])->name('users.delete');
        Route::get('/users/dataRoles', [usersController::class, 'dataRoles'])->name('users.dataRoles');
        Route::post('/users/assignRoles', [usersController::class, 'assignRoles'])->name('users.assignRoles');
        Route::get('/users/{id}/getUserRoles', [usersController::class, 'getUserRoles'])->name('users.getUserRoles');

        // Roles
        Route::get('/auth/roles', [rolesController::class, 'roles'])->name('roles.roles');
        Route::get('/roles/tableRoles', [rolesController::class, 'table'])->name('roles.table');
        Route::post('/roles/store', [rolesController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}/edit', [rolesController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}/update', [rolesController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}/delete', [rolesController::class, 'destroy'])->name('roles.delete');
        Route::get('/roles/dataPermissions', [rolesController::class, 'dataPermissions'])->name('roles.dataPermissions');
        Route::post('/roles/assignPermissions', [rolesController::class, 'assignPermissions'])->name('roles.assignPermissions');
        Route::get('/roles/{id}/getRolePermissions', [rolesController::class, 'getRolePermissions'])->name('roles.getRolePermissions');

        // Permissions
        Route::get('/auth/permissions', [permissionsController::class, 'permissions'])->name('permissions.permissions');
        Route::get('/permissions/tablePermissions', [permissionsController::class, 'table'])->name('permissions.table');
        Route::post('/permissions/store', [permissionsController::class, 'store'])->name('permissions.store');
        Route::get('/permissions/{id}/edit', [permissionsController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{id}/update', [permissionsController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{id}/delete', [permissionsController::class, 'destroy'])->name('permissions.delete');
    });
});

require __DIR__.'/auth.php';
