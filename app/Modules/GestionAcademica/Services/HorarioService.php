<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\GestionAcademica\Models\Horario;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HorarioService
{
    public function list(): Collection
    {
        return Horario::query()
            ->withCount([
                'asignaciones as asignaciones_activas' => fn ($query) => $query->where('activo', true),
            ])
            ->orderByRaw("array_position(ARRAY['MANANA','TARDE','NOCHE'], turno)")
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn (Horario $horario) => $this->serialize($horario));
    }

    public function create(array $data): Horario
    {
        $this->validateUnique($data);

        return Horario::create([
            'turno' => $data['turno'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'activo' => true,
        ]);
    }

    public function update(Horario $horario, array $data): Horario
    {
        $this->validateUnique($data, $horario);

        $horario->update([
            'turno' => $data['turno'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'activo' => $data['activo'] ?? $horario->activo,
        ]);

        return $horario;
    }

    public function toggleActive(Horario $horario): Horario
    {
        if ($horario->activo && $horario->asignaciones()->where('activo', true)->exists()) {
            throw ValidationException::withMessages([
                'horario' => 'No se puede desactivar un horario con asignaciones academicas activas.',
            ]);
        }

        $horario->update(['activo' => ! $horario->activo]);

        return $horario;
    }

    public function serialize(Horario $horario): array
    {
        return [
            'id_horario' => $horario->id_horario,
            'dias_label' => 'Lunes a sabado',
            'turno' => $horario->turno,
            'turno_label' => GrupoAcademicoService::TURNOS[$horario->turno]['label'] ?? 'Sin turno',
            'hora_inicio' => substr((string) $horario->hora_inicio, 0, 5),
            'hora_fin' => substr((string) $horario->hora_fin, 0, 5),
            'activo' => (bool) $horario->activo,
            'asignaciones_activas' => (int) ($horario->asignaciones_activas ?? 0),
        ];
    }

    private function validateUnique(array $data, ?Horario $ignore = null): void
    {
        $exists = Horario::query()
            ->where('turno', $data['turno'])
            ->where('hora_inicio', $data['hora_inicio'])
            ->where('hora_fin', $data['hora_fin'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id_horario))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'Ya existe un horario con el mismo turno, hora de inicio y hora de fin.',
            ]);
        }
    }
}
