<?php

use App\Modules\GestionAcademica\Controllers\AsignacionAcademicaController;
use App\Modules\GestionAcademica\Controllers\AdmisionCupoController;
use App\Modules\GestionAcademica\Controllers\MateriaCupController;
use App\Modules\GestionAcademica\Controllers\GrupoAcademicoController;
use App\Modules\GestionAcademica\Controllers\DocenteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('academico')
    ->name('academico.')
    ->group(function () {
        Route::get('admision-cupos', [AdmisionCupoController::class, 'index'])
            ->middleware('permission:admision:read')
            ->name('admision-cupos.index');

        Route::post('admision-cupos/cupos', [AdmisionCupoController::class, 'upsertCupo'])
            ->middleware('permission:admision:update')
            ->name('admision-cupos.cupos.upsert');

        Route::post('admision-cupos/procesar', [AdmisionCupoController::class, 'process'])
            ->middleware('permission:admision:process')
            ->name('admision-cupos.process');

        Route::get('asignaciones', [AsignacionAcademicaController::class, 'index'])
            ->middleware('permission:asignaciones:read')
            ->name('asignaciones.index');

        Route::post('asignaciones', [AsignacionAcademicaController::class, 'store'])
            ->middleware('permission:asignaciones:create')
            ->name('asignaciones.store');

        Route::put('asignaciones/{asignacion}', [AsignacionAcademicaController::class, 'update'])
            ->middleware('permission:asignaciones:update')
            ->name('asignaciones.update');

        Route::patch('asignaciones/{asignacion}/toggle', [AsignacionAcademicaController::class, 'toggle'])
            ->middleware('permission:asignaciones:delete')
            ->name('asignaciones.toggle');

        Route::get('grupos', [GrupoAcademicoController::class, 'index'])
            ->middleware('permission:grupos:read')
            ->name('grupos.index');

        Route::post('grupos', [GrupoAcademicoController::class, 'store'])
            ->middleware('permission:grupos:create')
            ->name('grupos.store');

        Route::post('grupos/generar', [GrupoAcademicoController::class, 'generate'])
            ->middleware('permission:grupos:create')
            ->name('grupos.generate');

        Route::post('grupos/asignar-postulantes', [GrupoAcademicoController::class, 'assignPostulantes'])
            ->middleware('permission:grupos:update')
            ->name('grupos.assign-postulantes');

        Route::put('grupos/{grupo}', [GrupoAcademicoController::class, 'update'])
            ->middleware('permission:grupos:update')
            ->name('grupos.update');

        Route::patch('grupos/{grupo}/toggle', [GrupoAcademicoController::class, 'toggle'])
            ->middleware('permission:grupos:delete')
            ->name('grupos.toggle');

        Route::get('docentes', [DocenteController::class, 'index'])
            ->middleware('permission:docentes:read')
            ->name('docentes.index');

        Route::post('docentes', [DocenteController::class, 'store'])
            ->middleware('permission:docentes:create')
            ->name('docentes.store');

        Route::put('docentes/{docente}', [DocenteController::class, 'update'])
            ->middleware('permission:docentes:update')
            ->name('docentes.update');

        Route::patch('docentes/{docente}/toggle', [DocenteController::class, 'toggle'])
            ->middleware('permission:docentes:delete')
            ->name('docentes.toggle');

        Route::get('materias', [MateriaCupController::class, 'index'])
            ->middleware('permission:materias:read')
            ->name('materias.index');

        Route::post('materias', [MateriaCupController::class, 'store'])
            ->middleware('permission:materias:create')
            ->name('materias.store');

        Route::put('materias/{materia}', [MateriaCupController::class, 'update'])
            ->middleware('permission:materias:update')
            ->name('materias.update');

        Route::patch('materias/{materia}/toggle', [MateriaCupController::class, 'toggle'])
            ->middleware('permission:materias:delete')
            ->name('materias.toggle');
    });
