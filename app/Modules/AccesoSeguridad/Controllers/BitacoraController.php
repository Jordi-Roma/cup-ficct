<?php

namespace App\Modules\AccesoSeguridad\Controllers;

use App\Modules\AccesoSeguridad\Services\BitacoraService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BitacoraController extends BaseController
{
    public function __construct(private readonly BitacoraService $service) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(['search', 'operacion', 'fecha_inicio', 'fecha_fin']);
        
        return Inertia::render('acceso-seguridad/bitacora', [
            'logs' => $this->service->obtenerLogs($filtros),
            'filters' => $filtros,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(['search', 'operacion', 'fecha_inicio', 'fecha_fin']);
        return $this->service->exportCsv($filtros);
    }
}
