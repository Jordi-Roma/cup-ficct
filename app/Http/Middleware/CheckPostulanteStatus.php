<?php

namespace App\Http\Middleware;

use App\Modules\GestionAcademica\Models\GestionAcademica;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPostulanteStatus
{
    /**
     * Rutas permitidas para postulantes inhabilitados
     */
    protected array $except = [
        'logout',
        'postulante.repostulacion.*',
        'postulante.pago.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Solo aplica a usuarios autenticados con rol POSTULANTE
        if (! $user || ! $user->hasRole('POSTULANTE')) {
            return $next($request);
        }

        // Si intenta acceder a una ruta permitida, lo dejamos pasar
        if ($request->routeIs(...$this->except)) {
            return $next($request);
        }

        $postulante = $user->postulante()->with('postulaciones')->first();
        if (! $postulante) {
            return $next($request);
        }

        $gestionActiva = GestionAcademica::where('activo', true)->orderByDesc('id_gestion')->first();

        $postulacionActiva = $gestionActiva
            ? $postulante->postulaciones->firstWhere('id_gestion', $gestionActiva->id_gestion)
            : null;

        // Si ya está habilitado para la gestión actual, puede navegar a cualquier ruta
        if ($postulacionActiva?->estado_proceso === 'HABILITADO_CUP') {
            return $next($request);
        }

        // Si está en medio de un proceso de pago o repostulación, forzamos la redirección
        if ($postulacionActiva?->estado_proceso === 'VALIDADO_PENDIENTE_PAGO') {
            return redirect()->route('postulante.pago.index');
        }

        if ($postulante->documentacion_validada && $gestionActiva) {
            return redirect()->route('postulante.repostulacion.index');
        }

        // En caso de que no tenga acceso y no cumpla ninguna condición anterior, 
        // lo mandamos a la pantalla de repostulación para que vea los mensajes de error
        return redirect()->route('postulante.repostulacion.index');
    }
}
