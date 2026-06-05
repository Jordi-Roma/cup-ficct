<?php

namespace App\Modules\RegistroPostulantes\Actions;

use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'ci' => ['required', 'string', 'max:20', Rule::unique('usuario', 'ci')],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'string', 'email', 'max:150', Rule::unique('usuario', 'correo')],
            'telefono' => ['nullable', 'string', 'max:20'],
            'sexo' => ['required', Rule::in(['M', 'F', 'O'])],
            'fecha_nacimiento' => ['required', 'date'],
            'direccion' => ['nullable', 'string'],
            'colegio_procedencia' => ['nullable', 'string', 'max:150'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'presento_titulo_bachiller' => ['nullable', 'boolean'],
            'presento_fotocopia_carnet' => ['nullable', 'boolean'],
            'id_gestion' => ['required', 'integer', Rule::exists('gestion_academica', 'id_gestion')],
            'id_carrera_opcion1' => ['required', 'integer', Rule::exists('carrera', 'id_carrera')],
            'id_carrera_opcion2' => [
                'nullable',
                'integer',
                'different:id_carrera_opcion1',
                Rule::exists('carrera', 'id_carrera'),
            ],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'ci' => $input['ci'],
                'nombre' => $input['nombre'],
                'apellido' => $input['apellido'],
                'username' => 'SOL'.$input['ci'],
                'correo' => $input['correo'],
                'password_hash' => Str::random(32),
                'telefono' => $input['telefono'] ?? null,
                'sexo' => $input['sexo'],
                'estado_acceso' => 'BLOQUEADO',
                'activo' => false,
            ]);

            $postulante = Postulante::create([
                'id_usuario' => $user->id_usuario,
                'fecha_nacimiento' => $input['fecha_nacimiento'],
                'direccion' => $input['direccion'] ?? null,
                'colegio_procedencia' => $input['colegio_procedencia'] ?? null,
                'ciudad' => $input['ciudad'] ?? null,
                'documentacion_completa' => false,
                'presento_titulo_bachiller' => filter_var($input['presento_titulo_bachiller'] ?? false, FILTER_VALIDATE_BOOL),
                'presento_fotocopia_carnet' => filter_var($input['presento_fotocopia_carnet'] ?? false, FILTER_VALIDATE_BOOL),
                'documentacion_validada' => false,
                'creado_por_admin' => false,
                'requiere_pago' => true,
            ]);

            Postulacion::create([
                'id_postulante' => $postulante->id_postulante,
                'id_gestion' => $input['id_gestion'],
                'id_carrera_opcion1' => $input['id_carrera_opcion1'],
                'id_carrera_opcion2' => $input['id_carrera_opcion2'] ?? null,
                'estado_admision' => 'PENDIENTE',
                'estado_proceso' => 'PENDIENTE_VALIDACION',
            ]);

            $postulanteRole = Rol::where('nombre', 'POSTULANTE')->first();

            if ($postulanteRole !== null) {
                $user->roles()->syncWithoutDetaching([
                    $postulanteRole->id_rol => [
                        'activo' => true,
                        'fecha_asignacion' => now(),
                    ],
                ]);
            }

            return $user;
        });
    }
}
