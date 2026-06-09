<?php

namespace App\Modules\RegistroPostulantes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RepostulacionController extends Controller
{
    /**
     * Muestra la página de repostulación al postulante.
     * Solo accesible si tiene documentos validados y NO tiene una postulación
     * activa en la gestión activa actual.
     */
    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();
        $postulante = $user->postulante()->with('postulaciones.gestion')->first();

        if (! $postulante || ! $postulante->documentacion_validada) {
            return redirect()->route('home')
                ->with('error', 'No tienes un perfil de postulante con documentación validada.');
        }

        $gestionActiva = GestionAcademica::where('activo', true)->orderByDesc('id_gestion')->first();

        if (! $gestionActiva) {
            return redirect()->route('examenes.historial.index')
                ->with('error', 'No hay una gestión académica activa en este momento.');
        }

        // Si ya tiene postulación en la gestión activa, redirigir según su estado
        $postulacionActiva = $postulante->postulaciones
            ->firstWhere('id_gestion', $gestionActiva->id_gestion);

        if ($postulacionActiva) {
            if ($postulacionActiva->estado_proceso === 'VALIDADO_PENDIENTE_PAGO') {
                return redirect()->route('postulante.pago.index');
            }
            if ($postulacionActiva->estado_proceso === 'HABILITADO_CUP') {
                return redirect()->route('examenes.historial.index');
            }
        }

        // Historial de postulaciones anteriores para mostrar al usuario
        $historial = $postulante->postulaciones
            ->sortByDesc('fecha_postulacion')
            ->map(fn (Postulacion $p) => [
                'gestion'        => $p->gestion?->nombre,
                'estado_admision' => $p->estado_admision,
                'estado_proceso'  => $p->estado_proceso,
            ])
            ->values();

        $carreras = Carrera::where('activo', true)
            ->orderBy('nombre')
            ->get(['id_carrera as id', 'nombre']);

        return Inertia::render('registro-postulantes/repostulacion', [
            'postulante' => [
                'nombre_completo' => $user->name,
                'ci'              => $user->ci,
                'correo'          => $user->correo,
            ],
            'gestion_activa' => [
                'id'     => $gestionActiva->id_gestion,
                'nombre' => $gestionActiva->nombre,
            ],
            'carreras' => $carreras,
            'historial' => $historial,
        ]);
    }

    /**
     * Crea una nueva postulación para la gestión activa.
     * Salta la validación de documentos (ya fue validada en gestiones anteriores).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $postulante = $user->postulante()->with('postulaciones')->first();

        if (! $postulante || ! $postulante->documentacion_validada) {
            throw ValidationException::withMessages([
                'postulante' => 'No tienes documentación validada para repostularte.',
            ]);
        }

        $gestionActiva = GestionAcademica::where('activo', true)->orderByDesc('id_gestion')->first();

        if (! $gestionActiva) {
            throw ValidationException::withMessages([
                'gestion' => 'No hay una gestión académica activa.',
            ]);
        }

        // Verificar que no haya ya una postulación en esta gestión
        $yaExiste = $postulante->postulaciones
            ->contains('id_gestion', $gestionActiva->id_gestion);

        if ($yaExiste) {
            throw ValidationException::withMessages([
                'gestion' => 'Ya tienes una postulación registrada para la gestión activa.',
            ]);
        }

        $validated = $request->validate([
            'id_carrera_opcion1' => ['required', 'integer', Rule::exists('carrera', 'id_carrera')],
            'id_carrera_opcion2' => [
                'nullable',
                'integer',
                'different:id_carrera_opcion1',
                Rule::exists('carrera', 'id_carrera'),
            ],
            'turno_preferido' => ['required', Rule::in(['MANANA', 'TARDE', 'NOCHE'])],
        ]);

        // Crear la nueva postulación directamente en VALIDADO_PENDIENTE_PAGO
        // (documentos ya validados, solo requiere pago)
        Postulacion::create([
            'id_postulante'      => $postulante->id_postulante,
            'id_gestion'         => $gestionActiva->id_gestion,
            'id_carrera_opcion1' => $validated['id_carrera_opcion1'],
            'id_carrera_opcion2' => $validated['id_carrera_opcion2'] ?? null,
            'estado_admision'    => 'PENDIENTE',
            'estado_proceso'     => 'VALIDADO_PENDIENTE_PAGO',
            'turno_preferido'    => $validated['turno_preferido'],
        ]);

        // Asegurarse de que la cuenta está habilitada y requiere pago
        $postulante->update(['requiere_pago' => true]);
        $user->update(['activo' => true, 'estado_acceso' => 'HABILITADO']);

        return redirect()->route('postulante.pago.index')
            ->with('success', 'Repostulación registrada. Completa el pago para habilitar tu acceso a la nueva gestión.');
    }
}
