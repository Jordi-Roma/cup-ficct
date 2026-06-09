<?php

use App\Modules\Examenes\Controllers\HistorialAcademicoController;
use App\Modules\Examenes\Controllers\MisAsignacionesController;
use App\Modules\Examenes\Controllers\NotaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('examenes')
    ->name('examenes.')
    ->group(function () {
        Route::get('mis-asignaciones', [MisAsignacionesController::class, 'index'])
            ->middleware('permission:mis-asignaciones:read')
            ->name('mis-asignaciones.index');

        Route::get('historial', [HistorialAcademicoController::class, 'index'])
            ->middleware('permission:historial:read-own')
            ->name('historial.index');

        Route::get('notas', [NotaController::class, 'index'])
            ->middleware('permission:notas:read')
            ->name('notas.index');

        Route::post('notas', [NotaController::class, 'store'])
            ->middleware('permission:notas:create')
            ->name('notas.store');

        Route::post('notas/lote', [NotaController::class, 'batchStore'])
            ->middleware('permission:notas:create')
            ->name('notas.batch-store');

        Route::post('notas/generar-prueba', [NotaController::class, 'generateTestScores'])
            ->middleware('permission:notas:create')
            ->name('notas.generate-test-scores');

        Route::put('notas/{nota}', [NotaController::class, 'update'])
            ->middleware('permission:notas:update')
            ->name('notas.update');
    });
