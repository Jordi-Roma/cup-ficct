<?php

namespace App\Modules\AccesoSeguridad\Services;

use App\Modules\AccesoSeguridad\Models\LogAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BitacoraService
{
    public function obtenerLogs(array $filtros): LengthAwarePaginator
    {
        return $this->buildQuery($filtros)
            ->with('usuario')
            ->orderByDesc('fecha_operacion')
            ->paginate(15)
            ->withQueryString();
    }

    public function exportCsv(array $filtros): StreamedResponse
    {
        $query = $this->buildQuery($filtros)
            ->with('usuario')
            ->orderByDesc('fecha_operacion');

        return response()->streamDownload(function () use ($query): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID Log',
                'Fecha',
                'Usuario',
                'IP Origen',
                'Operacion',
                'Tabla Afectada',
                'ID Registro',
                'Datos Anteriores',
                'Datos Nuevos'
            ]);

            $query->chunk(1000, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->id_log,
                        $log->fecha_operacion?->format('Y-m-d H:i:s'),
                        $log->usuario ? "{$log->usuario->name} ({$log->usuario->correo})" : 'Sistema',
                        $log->ip_origen,
                        $log->operacion,
                        $log->tabla_afectada,
                        $log->id_registro,
                        json_encode($log->datos_anteriores),
                        json_encode($log->datos_nuevos),
                    ]);
                }
            });

            fclose($file);
        }, 'bitacora-auditoria.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildQuery(array $filtros): Builder
    {
        $query = LogAuditoria::query();

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('tabla_afectada', 'ilike', "%{$search}%")
                  ->orWhere('id_registro', 'ilike', "%{$search}%")
                  ->orWhereHas('usuario', function (Builder $uq) use ($search) {
                      $uq->where('nombre', 'ilike', "%{$search}%")
                         ->orWhere('apellido', 'ilike', "%{$search}%")
                         ->orWhere('correo', 'ilike', "%{$search}%");
                  });
            });
        }

        if (!empty($filtros['operacion'])) {
            $query->where('operacion', $filtros['operacion']);
        }

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('fecha_operacion', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('fecha_operacion', '<=', $filtros['fecha_fin']);
        }

        return $query;
    }
}
