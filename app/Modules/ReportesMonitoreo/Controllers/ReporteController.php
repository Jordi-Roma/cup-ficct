<?php

namespace App\Modules\ReportesMonitoreo\Controllers;

use App\Modules\ReportesMonitoreo\Services\ReporteService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends BaseController
{
    public function __construct(private readonly ReporteService $service) {}

    public function dashboard(Request $request): Response
    {
        $gestion = $this->service->resolveGestion(
            filled($request->query('id_gestion')) ? (int) $request->query('id_gestion') : null,
        );

        return Inertia::render('dashboard', [
            'gestiones' => $this->service->gestiones(),
            'selectedGestion' => [
                'id_gestion' => $gestion->id_gestion,
                'nombre' => $gestion->nombre,
                'activo' => (bool) $gestion->activo,
            ],
            'resumen' => $this->service->dashboard($gestion->id_gestion),
            'estadisticasPorMateria' => $this->service->estadisticasPorMateria($gestion->id_gestion),
            'filters' => $request->only(['id_gestion']),
        ]);
    }

    public function index(Request $request): Response
    {
        $gestion = $this->service->resolveGestion(
            filled($request->query('id_gestion')) ? (int) $request->query('id_gestion') : null,
        );

        return Inertia::render('reportes-monitoreo/reportes', [
            'gestiones' => $this->service->gestiones(),
            'selectedGestion' => [
                'id_gestion' => $gestion->id_gestion,
                'nombre' => $gestion->nombre,
                'activo' => (bool) $gestion->activo,
            ],
            'resumen' => $this->service->dashboard($gestion->id_gestion),
            'listaGeneral' => $this->service->listaGeneralPostulantes($gestion->id_gestion),
            'aprobados' => $this->service->postulantesAprobados($gestion->id_gestion),
            'reprobados' => $this->service->postulantesReprobados($gestion->id_gestion),
            'promediosPorPostulante' => $this->service->promediosPorPostulante($gestion->id_gestion),
            'estadisticasPorMateria' => $this->service->estadisticasPorMateria($gestion->id_gestion),
            'grupos' => $this->service->gruposConCapacidad($gestion->id_gestion),
            'docentesPorGrupo' => $this->service->docentesPorGrupo($gestion->id_gestion),
            'gruposConMasAprobados' => $this->service->gruposConMasAprobados($gestion->id_gestion),
            'filters' => $request->only(['id_gestion']),
        ]);
    }

    public function export(Request $request, string $tipo): StreamedResponse
    {
        $gestion = $this->service->resolveGestion(
            filled($request->query('id_gestion')) ? (int) $request->query('id_gestion') : null,
        );

        return $this->service->exportCsv($tipo, $gestion->id_gestion);
    }
}
