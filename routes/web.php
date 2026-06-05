<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return inertia('welcome');
})->name('home');

require app_path('Modules/AccesoSeguridad/Routes/web.php');
require app_path('Modules/RegistroPostulantes/Routes/web.php');
require app_path('Modules/GestionAcademica/Routes/web.php');
require app_path('Modules/Examenes/Routes/web.php');
require app_path('Modules/ReportesMonitoreo/Routes/web.php');
