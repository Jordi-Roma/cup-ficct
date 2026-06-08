<?php

use App\Modules\GestionAcademica\Controllers\AsignacionAcademicaController;
use App\Modules\GestionAcademica\Controllers\AulaController;
use App\Modules\GestionAcademica\Controllers\GestionAcademicaController;
use App\Modules\GestionAcademica\Controllers\MateriaCupController;
use App\Modules\GestionAcademica\Controllers\GrupoAcademicoController;
use App\Modules\GestionAcademica\Controllers\DocenteController;
use App\Modules\GestionAcademica\Controllers\HorarioController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('academico')
    ->name('academico.')
    ->group(function () {
        Route::get('gestiones', [GestionAcademicaController::class, 'index'])
            ->middleware('permission:gestiones:read')
            ->name('gestiones.index');

        Route::post('gestiones', [GestionAcademicaController::class, 'store'])
            ->middleware('permission:gestiones:create')
            ->name('gestiones.store');

        Route::put('gestiones/{gestion}', [GestionAcademicaController::class, 'update'])
            ->middleware('permission:gestiones:update')
            ->name('gestiones.update');

        Route::patch('gestiones/{gestion}/toggle', [GestionAcademicaController::class, 'toggle'])
            ->middleware('permission:gestiones:delete')
            ->name('gestiones.toggle');

        Route::get('asignaciones', [AsignacionAcademicaController::class, 'index'])
            ->middleware('permission:asignaciones:read')
            ->name('asignaciones.index');

        Route::post('asignaciones', [AsignacionAcademicaController::class, 'store'])
            ->middleware('permission:asignaciones:create')
            ->name('asignaciones.store');

        Route::post('asignaciones/asignar-postulantes', [AsignacionAcademicaController::class, 'assignPostulantes'])
            ->middleware('permission:asignaciones:update')
            ->name('asignaciones.assign-postulantes');

        Route::post('asignaciones/generar', [AsignacionAcademicaController::class, 'generate'])
            ->middleware('permission:asignaciones:create')
            ->name('asignaciones.generate');

        Route::put('asignaciones/{asignacion}', [AsignacionAcademicaController::class, 'update'])
            ->middleware('permission:asignaciones:update')
            ->name('asignaciones.update');

        Route::patch('asignaciones/{asignacion}/toggle', [AsignacionAcademicaController::class, 'toggle'])
            ->middleware('permission:asignaciones:delete')
            ->name('asignaciones.toggle');

        Route::get('aulas', [AulaController::class, 'index'])
            ->middleware('permission:aulas:read')
            ->name('aulas.index');

        Route::post('aulas', [AulaController::class, 'store'])
            ->middleware('permission:aulas:create')
            ->name('aulas.store');

        Route::put('aulas/{aula}', [AulaController::class, 'update'])
            ->middleware('permission:aulas:update')
            ->name('aulas.update');

        Route::patch('aulas/{aula}/toggle', [AulaController::class, 'toggle'])
            ->middleware('permission:aulas:delete')
            ->name('aulas.toggle');

        Route::get('horarios', [HorarioController::class, 'index'])
            ->middleware('permission:horarios:read')
            ->name('horarios.index');

        Route::post('horarios', [HorarioController::class, 'store'])
            ->middleware('permission:horarios:create')
            ->name('horarios.store');

        Route::put('horarios/{horario}', [HorarioController::class, 'update'])
            ->middleware('permission:horarios:update')
            ->name('horarios.update');

        Route::patch('horarios/{horario}/toggle', [HorarioController::class, 'toggle'])
            ->middleware('permission:horarios:delete')
            ->name('horarios.toggle');

        Route::get('grupos', [GrupoAcademicoController::class, 'index'])
            ->middleware('permission:grupos:read')
            ->name('grupos.index');

        Route::post('grupos', [GrupoAcademicoController::class, 'store'])
            ->middleware('permission:grupos:create')
            ->name('grupos.store');

        Route::post('grupos/generar', [GrupoAcademicoController::class, 'generate'])
            ->middleware('permission:grupos:create')
            ->name('grupos.generate');

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
