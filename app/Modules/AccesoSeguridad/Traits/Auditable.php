<?php

namespace App\Modules\AccesoSeguridad\Traits;

use App\Modules\AccesoSeguridad\Models\LogAuditoria;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::registrarLog('INSERT', $model);
        });

        static::updated(function ($model) {
            static::registrarLog('UPDATE', $model);
        });

        static::deleted(function ($model) {
            static::registrarLog('DELETE', $model);
        });
    }

    protected static function registrarLog(string $operacion, $model): void
    {
        $datosAnteriores = null;
        $datosNuevos = null;

        if ($operacion === 'INSERT') {
            $datosNuevos = $model->getAttributes();
        } elseif ($operacion === 'UPDATE') {
            $datosAnteriores = array_intersect_key($model->getOriginal(), $model->getDirty());
            $datosNuevos = $model->getDirty();
        } elseif ($operacion === 'DELETE') {
            $datosAnteriores = $model->getAttributes();
        }

        $idRegistro = $model->getKey();
        if (is_array($idRegistro)) {
            $idRegistro = json_encode($idRegistro);
        }

        LogAuditoria::create([
            'tabla_afectada' => $model->getTable(),
            'operacion' => $operacion,
            'id_registro' => $idRegistro ? (string) $idRegistro : null,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'id_usuario' => Auth::id(),
            'ip_origen' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
