<?php

namespace App\Modules\RegistroPostulantes\Services;

use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\RegistroPostulantes\Notifications\PostulanteCredentialsNotification;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class PostulanteSolicitudService
{
    public function list(): Collection
    {
        return Postulante::query()
            ->with(['usuario', 'postulaciones.carreraOpcion1', 'postulaciones.carreraOpcion2', 'postulaciones.gestion'])
            ->whereHas('postulaciones', fn ($query) => $query->where('estado_proceso', 'PENDIENTE_VALIDACION'))
            ->orderByDesc('id_postulante')
            ->get()
            ->map(fn (Postulante $postulante) => $this->serialize($postulante))
            ->values();
    }

    public function confirm(Postulante $postulante, int $validatedBy): array
    {
        if (! $postulante->presento_titulo_bachiller || ! $postulante->presento_fotocopia_carnet) {
            throw ValidationException::withMessages([
                'documentos' => 'No se puede confirmar la solicitud sin titulo de bachiller y fotocopia de carnet.',
            ]);
        }

        return DB::transaction(function () use ($postulante, $validatedBy): array {
            $postulante->loadMissing(['usuario', 'postulaciones']);
            $user = $postulante->usuario;
            $username = 'P'.$user->ci;

            $exists = $user::query()
                ->where('username', $username)
                ->where('id_usuario', '<>', $user->id_usuario)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'username' => 'Ya existe un usuario generado para este CI.',
                ]);
            }

            $password = $this->generatePassword();

            $user->update([
                'username' => $username,
                'password_hash' => $password,
                'estado_acceso' => 'HABILITADO',
                'activo' => true,
            ]);

            $postulante->update([
                'documentacion_validada' => true,
                'documentacion_completa' => true,
                'validado_por' => $validatedBy,
                'fecha_validacion_documentos' => now(),
            ]);

            $postulacion = $this->currentPostulacion($postulante);
            $postulacion?->update([
                'estado_proceso' => 'VALIDADO_PENDIENTE_PAGO',
            ]);

            $postulanteRole = Rol::where('nombre', 'POSTULANTE')->first();

            if ($postulanteRole) {
                $user->roles()->syncWithoutDetaching([
                    $postulanteRole->id_rol => [
                        'activo' => true,
                        'fecha_asignacion' => now(),
                    ],
                ]);
            }

            $this->sendCredentials($postulante->refresh()->load('usuario'), $username, $password);

            return [
                'username' => $username,
                'password' => $password,
            ];
        });
    }

    public function reject(Postulante $postulante): void
    {
        DB::transaction(function () use ($postulante): void {
            $postulante->loadMissing(['usuario', 'postulaciones']);

            $postulante->usuario?->update([
                'activo' => false,
                'estado_acceso' => 'BLOQUEADO',
            ]);

            $this->currentPostulacion($postulante)?->update([
                'estado_proceso' => 'RECHAZADO',
            ]);
        });
    }

    public function serialize(Postulante $postulante): array
    {
        $user = $postulante->usuario;
        $postulacion = $this->currentPostulacion($postulante);

        return [
            'id_postulante' => $postulante->id_postulante,
            'ci' => $user?->ci,
            'nombre_completo' => $user?->name,
            'correo' => $user?->correo,
            'telefono' => $user?->telefono,
            'ciudad' => $postulante->ciudad,
            'colegio_procedencia' => $postulante->colegio_procedencia,
            'presento_titulo_bachiller' => (bool) $postulante->presento_titulo_bachiller,
            'presento_fotocopia_carnet' => (bool) $postulante->presento_fotocopia_carnet,
            'documentacion_validada' => (bool) $postulante->documentacion_validada,
            'documentacion_completa' => (bool) $postulante->documentacion_completa,
            'estado_proceso' => $postulacion?->estado_proceso,
            'turno_preferido' => $postulacion?->turno_preferido,
            'turno_preferido_label' => $this->turnoLabel($postulacion?->turno_preferido),
            'carrera_opcion1' => $postulacion?->carreraOpcion1?->nombre,
            'carrera_opcion2' => $postulacion?->carreraOpcion2?->nombre,
        ];
    }

    private function currentPostulacion(Postulante $postulante): ?Postulacion
    {
        $postulaciones = $postulante->relationLoaded('postulaciones')
            ? $postulante->postulaciones
            : $postulante->postulaciones()->with(['carreraOpcion1', 'carreraOpcion2'])->get();

        return $postulaciones->sortByDesc('fecha_postulacion')->first();
    }

    private function generatePassword(): string
    {
        $symbols = '!@#$%&*?';
        $characters = [
            chr(random_int(65, 90)),
            chr(random_int(97, 122)),
            (string) random_int(0, 9),
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        while (count($characters) < 8) {
            $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'.$symbols;
            $characters[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        shuffle($characters);

        return implode('', $characters);
    }

    private function sendCredentials(Postulante $postulante, string $username, string $password): void
    {
        $to = env('POSTULANTE_NOTIFICATION_EMAIL', config('mail.from.address'));

        if (! $to) {
            return;
        }

        $user = $postulante->usuario;

        if (config('mail.default') === 'array') {
            logger()->info('Credenciales generadas para postulante CUP-FICCT', [
                'destino' => $to,
                'nombre' => $user?->name,
                'ci' => $user?->ci,
                'username' => $username,
                'password' => $password,
                'estado_proceso' => 'VALIDADO_PENDIENTE_PAGO',
            ]);

            return;
        }

        Notification::route('mail', $to)->notify(new PostulanteCredentialsNotification(
            $user?->name ?? '',
            $user?->ci ?? '',
            $username,
            $password,
        ));
    }

    private function turnoLabel(?string $turno): string
    {
        return [
            'MANANA' => 'Mañana',
            'TARDE' => 'Tarde',
            'NOCHE' => 'Noche',
        ][$turno] ?? 'Sin turno';
    }
}
