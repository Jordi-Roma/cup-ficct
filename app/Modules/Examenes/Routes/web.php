<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('examenes')
    ->name('examenes.')
    ->group(function () {
        //
    });
