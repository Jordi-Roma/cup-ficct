<?php

use App\Modules\RegistroPostulantes\Controllers\PostulanteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('postulantes')
    ->name('postulantes.')
    ->group(function () {
        Route::get('/', [PostulanteController::class, 'index'])
            ->middleware('permission:postulantes:read')
            ->name('index');

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
