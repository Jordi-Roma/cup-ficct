<?php

namespace App\Modules\Examenes\Controllers;

use App\Modules\Examenes\Models\Nota;
use App\Modules\Examenes\Requests\GenerateNotasPruebaRequest;
use App\Modules\Examenes\Requests\StoreNotaRequest;
use App\Modules\Examenes\Requests\UpdateNotaRequest;
use App\Modules\Examenes\Services\NotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class NotaController extends BaseController
{
    public function __construct(private readonly NotaService $service) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'id_grupo',
            'id_materia',
            'nro_examen',
            'search',
            'estado_final',
        ]);
        $postulantes = $this->service->list($filters, $request->user());

        return Inertia::render('examenes/notas', [
            'postulantes' => $postulantes,
            'options' => $this->service->getFormOptions($request->user()),
            'filters' => $filters,
            'resumen' => $this->service->resumen($postulantes),
            'notasGenerateSummary' => session('notas_generate_summary'),
        ]);
    }

    public function store(StoreNotaRequest $request): RedirectResponse
    {
        $this->service->store($request->validated(), $request->user());

        return back()->with('success', 'Nota registrada correctamente.');
    }

    public function update(UpdateNotaRequest $request, Nota $nota): RedirectResponse
    {
        $this->service->update($nota, $request->validated(), $request->user());

        return back()->with('success', 'Nota actualizada correctamente.');
    }

    public function batchStore(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'notas' => ['required', 'array'],
            'notas.*.id_nota' => ['nullable', 'exists:nota,id_nota'],
            'notas.*.id_postulacion' => ['required', 'exists:postulacion,id_postulacion'],
            'notas.*.id_materia' => ['required', 'exists:materia_cup,id_materia'],
            'notas.*.nro_examen' => ['required', 'integer', 'in:1,2,3'],
            'notas.*.nota' => ['required', 'numeric', 'min:0', 'max:100'],
        ])->validate();

        $result = $this->service->upsertBatch($validated['notas'], $request->user());

        return back()->with('success', "Notas guardadas correctamente. Creadas: {$result['created']}, actualizadas: {$result['updated']}.");
    }

    public function generateTestScores(GenerateNotasPruebaRequest $request): RedirectResponse
    {
        $summary = $this->service->generateTestScores($request->validated(), $request->user());

        return back()
            ->with('success', "Notas generadas: {$summary['creadas']}. Omitidas: {$summary['omitidas']}.")
            ->with('notas_generate_summary', $summary);
    }
}
