<?php

namespace App\Modules\RegistroPostulantes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\RegistroPostulantes\Models\Postulante;
use App\Modules\RegistroPostulantes\Requests\UpdatePostulanteRequest;
use App\Modules\RegistroPostulantes\Services\PostulanteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostulanteController extends Controller
{
    public function __construct(private readonly PostulanteService $postulanteService)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search',
            'ciudad',
            'colegio_procedencia',
            'documentacion_completa',
            'estado_admision',
            'id_carrera',
            'gestion',
        ]);
        $filters['gestion'] = $filters['gestion'] ?? 'activa';
        $gestionActiva = $this->gestionActiva();

        return Inertia::render('registro-postulantes/postulantes', [
            'postulantes' => $this->postulanteService->list($filters),
            'filters' => $filters,
            'carreras' => Carrera::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id_carrera', 'nombre']),
            'gestiones' => $this->gestiones(),
            'gestionActiva' => $gestionActiva ? [
                'id_gestion' => $gestionActiva->id_gestion,
                'nombre' => $gestionActiva->nombre,
                'activo' => $gestionActiva->activo,
            ] : null,
        ]);
    }

    public function show(Postulante $postulante): Response
    {
        return Inertia::render('registro-postulantes/postulantes', [
            'postulantes' => collect([$this->postulanteService->find($postulante)]),
            'filters' => [],
            'carreras' => Carrera::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id_carrera', 'nombre']),
            'gestiones' => $this->gestiones(),
            'gestionActiva' => $this->gestionActiva(),
            'selectedPostulante' => $this->postulanteService->find($postulante),
        ]);
    }

    public function update(UpdatePostulanteRequest $request, Postulante $postulante): RedirectResponse
    {
        $this->postulanteService->update($postulante, $request->validated());

        return back()->with('success', 'Postulante actualizado correctamente.');
    }

    public function toggle(Postulante $postulante): RedirectResponse
    {
        $this->postulanteService->toggleActive($postulante);

        return back()->with('success', 'Estado del postulante actualizado.');
    }

    private function gestionActiva(): ?GestionAcademica
    {
        return GestionAcademica::query()
            ->where('activo', true)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_gestion')
            ->first(['id_gestion', 'nombre', 'activo']);
    }

    private function gestiones()
    {
        return GestionAcademica::query()
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_gestion')
            ->get(['id_gestion', 'nombre', 'activo']);
    }
}
