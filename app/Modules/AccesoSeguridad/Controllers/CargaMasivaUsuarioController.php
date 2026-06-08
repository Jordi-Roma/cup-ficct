<?php

namespace App\Modules\AccesoSeguridad\Controllers;

use App\Modules\AccesoSeguridad\Requests\StoreCargaMasivaUsuarioRequest;
use App\Modules\AccesoSeguridad\Services\CargaMasivaUsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CargaMasivaUsuarioController extends BaseController
{
    public function __construct(private readonly CargaMasivaUsuarioService $service) {}

    public function index(Request $request): Response
    {
        return Inertia::render('acceso-seguridad/carga-masiva', [
            'resultado' => $request->session()->get('resultado_carga_masiva'),
        ]);
    }

    public function store(StoreCargaMasivaUsuarioRequest $request): RedirectResponse
    {
        $summary = $this->service->import(
            $request->validated('tipo_usuario'),
            $request->file('archivo_csv'),
            $request->user(),
        );

        $message = "Carga masiva completada. Creados: {$summary['creados']}. Omitidos: {$summary['omitidos']}. Los usuarios fueron creados con las contrasenas indicadas en el CSV.";

        return back()
            ->with('success', $message)
            ->with('resultado_carga_masiva', $summary);
    }
}
