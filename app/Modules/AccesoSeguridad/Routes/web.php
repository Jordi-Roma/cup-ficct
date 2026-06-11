<?php

use App\Modules\AccesoSeguridad\Controllers\ProfileController;
use App\Modules\AccesoSeguridad\Controllers\BitacoraController;
use App\Modules\AccesoSeguridad\Controllers\CargaMasivaUsuarioController;
use App\Modules\AccesoSeguridad\Controllers\PasswordResetController;
use App\Modules\AccesoSeguridad\Controllers\PermisoController;
use App\Modules\AccesoSeguridad\Controllers\RolController;
use App\Modules\AccesoSeguridad\Controllers\SecurityController;
use App\Modules\AccesoSeguridad\Controllers\UsuarioController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::get('forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('reset-password/verify', [PasswordResetController::class, 'verifyForm'])->name('password.verify.form');
    Route::post('reset-password/verify', [PasswordResetController::class, 'verify'])->name('password.verify');
    Route::get('reset-password', [PasswordResetController::class, 'edit'])->name('password.reset.form');
    Route::get('reset-password/{token}', fn () => redirect()->route('password.verify.form'))->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

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
    Route::post('usuarios', [UsuarioController::class, 'store'])->middleware('permission:usuarios:create')->name('usuarios.store');
    Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->middleware('permission:usuarios:update')->name('usuarios.update');
    Route::put('usuarios/{usuario}/roles', [UsuarioController::class, 'syncRoles'])->middleware('permission:usuarios:update')->name('usuarios.roles.sync');

    Route::get('carga-masiva', [CargaMasivaUsuarioController::class, 'index'])->middleware('permission:usuarios:create')->name('carga-masiva.index');
    Route::post('carga-masiva', [CargaMasivaUsuarioController::class, 'store'])->middleware('permission:usuarios:create')->name('carga-masiva.store');

    Route::get('bitacora', [BitacoraController::class, 'index'])->middleware('permission:bitacora:read')->name('bitacora.index');
    Route::get('bitacora/export', [BitacoraController::class, 'export'])->middleware('permission:bitacora:read')->name('bitacora.export');
});
