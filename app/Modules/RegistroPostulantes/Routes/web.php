<?php

use App\Modules\RegistroPostulantes\Controllers\AdmisionCupoController;
use App\Modules\RegistroPostulantes\Controllers\PagoPostulanteController;
use App\Modules\RegistroPostulantes\Controllers\PostulanteController;
use App\Modules\RegistroPostulantes\Controllers\PostulanteSolicitudController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('postulante')->name('postulante.')->group(function () {
    Route::get('pago', [PagoPostulanteController::class, 'index'])->name('pago.index');
    Route::post('pago/stripe', [PagoPostulanteController::class, 'stripe'])->name('pago.stripe');
    Route::get('pago/exito', [PagoPostulanteController::class, 'exito'])->name('pago.exito');
    Route::get('pago/cancelado', [PagoPostulanteController::class, 'cancelado'])->name('pago.cancelado');
});

Route::middleware(['auth'])
    ->prefix('postulantes')
    ->name('postulantes.')
    ->group(function () {
        Route::get('/', [PostulanteController::class, 'index'])
            ->middleware('permission:postulantes:read')
            ->name('index');

        Route::get('solicitudes', [PostulanteSolicitudController::class, 'index'])
            ->middleware('permission:postulantes:update')
            ->name('solicitudes.index');

        Route::get('admision-cupos', [AdmisionCupoController::class, 'index'])
            ->middleware('permission:admision:read')
            ->name('admision-cupos.index');

        Route::post('admision-cupos/cupos', [AdmisionCupoController::class, 'upsertCupo'])
            ->middleware('permission:admision:update')
            ->name('admision-cupos.cupos.upsert');

        Route::post('admision-cupos/procesar', [AdmisionCupoController::class, 'process'])
            ->middleware('permission:admision:process')
            ->name('admision-cupos.process');

        Route::patch('solicitudes/{postulante}/confirmar', [PostulanteSolicitudController::class, 'confirmar'])
            ->middleware('permission:postulantes:update')
            ->name('solicitudes.confirmar');

        Route::patch('solicitudes/{postulante}/rechazar', [PostulanteSolicitudController::class, 'rechazar'])
            ->middleware('permission:postulantes:update')
            ->name('solicitudes.rechazar');

        Route::get('{postulante}', [PostulanteController::class, 'show'])
            ->middleware('permission:postulantes:read')
            ->name('show');

        Route::put('{postulante}', [PostulanteController::class, 'update'])
            ->middleware('permission:postulantes:update')
            ->name('update');

        Route::patch('{postulante}/toggle', [PostulanteController::class, 'toggle'])
            ->middleware('permission:postulantes:update')
            ->name('toggle');
    });
