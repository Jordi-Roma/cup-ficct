<?php

namespace App\Modules\Examenes\Controllers;

use App\Modules\Examenes\Services\MisAsignacionesService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MisAsignacionesController extends BaseController
{
    public function __construct(private readonly MisAsignacionesService $service) {}

    public function index(Request $request): Response
    {
        $asignaciones = $this->service->listForUser($request->user());

        return Inertia::render('examenes/mis-asignaciones', [
            'asignaciones' => $asignaciones,
            'resumen' => $this->service->resumen($asignaciones),
        ]);
    }
}
