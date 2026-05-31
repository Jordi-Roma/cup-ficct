<?php

use App\Modules\Autenticacion\Controllers\ProfileController;
use App\Modules\Autenticacion\Controllers\PermisoController;
use App\Modules\Autenticacion\Controllers\RolController;
use App\Modules\Autenticacion\Controllers\SecurityController;
use App\Modules\Autenticacion\Controllers\UsuarioController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('roles', [RolController::class, 'index'])->middleware('permission:roles:read')->name('roles.index');
    Route::post('roles', [RolController::class, 'store'])->middleware('permission:roles:create')->name('roles.store');
    Route::put('roles/{rol}', [RolController::class, 'update'])->middleware('permission:roles:update')->name('roles.update');
    Route::patch('roles/{rol}/toggle', [RolController::class, 'toggle'])->middleware('permission:roles:delete')->name('roles.toggle');

    Route::get('permisos', [PermisoController::class, 'index'])->middleware('permission:permisos:read')->name('permisos.index');
    Route::post('permisos', [PermisoController::class, 'store'])->middleware('permission:permisos:create')->name('permisos.store');
    Route::put('permisos/{permiso}', [PermisoController::class, 'update'])->middleware('permission:permisos:update')->name('permisos.update');
    Route::patch('permisos/{permiso}/toggle', [PermisoController::class, 'toggle'])->middleware('permission:permisos:delete')->name('permisos.toggle');

    Route::get('usuarios', [UsuarioController::class, 'index'])->middleware('permission:usuarios:read')->name('usuarios.index');
    Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->middleware('permission:usuarios:update')->name('usuarios.update');
    Route::put('usuarios/{usuario}/roles', [UsuarioController::class, 'syncRoles'])->middleware('permission:usuarios:update')->name('usuarios.roles.sync');
});
