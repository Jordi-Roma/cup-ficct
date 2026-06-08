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
        $filters = $this->service->resolveReportFilters($request->only([
            'id_gestion',
            'id_grupo',
            'id_materia',
            'estado',
        ]));
        $gestion = $this->service->resolveGestion($filters['id_gestion']);

        return Inertia::render('reportes-monitoreo/reportes', [
            'gestiones' => $this->service->gestiones(),
            'selectedGestion' => [
                'id_gestion' => $gestion->id_gestion,
                'nombre' => $gestion->nombre,
                'activo' => (bool) $gestion->activo,
            ],
            'options' => $this->service->reportOptions($filters),
            'resumen' => $this->service->dashboard($filters),
            'listaGeneral' => $this->service->listaGeneralPostulantes($filters),
            'aprobados' => $this->service->postulantesAprobados($filters),
            'reprobados' => $this->service->postulantesReprobados($filters),
            'promediosPorPostulante' => $this->service->promediosPorPostulante($filters),
            'estadisticasPorMateria' => $this->service->estadisticasPorMateria($filters),
            'grupos' => $this->service->gruposConCapacidad($filters),
            'docentesPorGrupo' => $this->service->docentesPorGrupo($filters),
            'gruposConMasAprobados' => $this->service->gruposConMasAprobados($filters),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, string $tipo): StreamedResponse
    {
        $filters = $this->service->resolveReportFilters($request->only([
            'id_gestion',
            'id_grupo',
            'id_materia',
            'estado',
        ]));

        return $this->service->exportCsv($tipo, $filters);
    }
}
