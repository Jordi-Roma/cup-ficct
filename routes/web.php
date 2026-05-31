<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

require app_path('Modules/Autenticacion/Routes/web.php');
require app_path('Modules/RegistroPostulantes/Routes/web.php');
require app_path('Modules/GestionAcademica/Routes/web.php');
require app_path('Modules/Examenes/Routes/web.php');
require app_path('Modules/ReportesMonitoreo/Routes/web.php');
