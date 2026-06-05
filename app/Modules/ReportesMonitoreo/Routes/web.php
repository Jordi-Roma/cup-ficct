<?php

use App\Modules\ReportesMonitoreo\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ReporteController::class, 'dashboard'])
        ->name('dashboard');
});

Route::middleware(['auth', 'verified'])
    ->prefix('reportes')
    ->name('reportes.')
    ->group(function () {
        Route::get('/', [ReporteController::class, 'index'])
            ->middleware('permission:reportes:read')
            ->name('index');

        Route::get('export/{tipo}', [ReporteController::class, 'export'])
            ->middleware('permission:reportes:export')
            ->name('export');
            
    });
