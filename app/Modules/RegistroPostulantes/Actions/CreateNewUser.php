<?php

namespace App\Modules\RegistroPostulantes\Actions;

use App\Modules\Autenticacion\Models\User;
use App\Modules\Autenticacion\Concerns\PasswordValidationRules;
use App\Modules\Autenticacion\Models\Rol;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

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
            'username' => ['required', 'string', 'max:50', Rule::unique('usuario', 'username')],
            'correo' => ['required', 'string', 'email', 'max:150', Rule::unique('usuario', 'correo')],
            'password' => $this->passwordRules(),
            'telefono' => ['nullable', 'string', 'max:20'],
            'sexo' => ['required', Rule::in(['M', 'F', 'O'])],
            'fecha_nacimiento' => ['required', 'date'],
            'direccion' => ['nullable', 'string'],
            'colegio_procedencia' => ['nullable', 'string', 'max:150'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'documentacion_completa' => ['required', 'boolean'],
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
                'username' => $input['username'],
                'correo' => $input['correo'],
                'password_hash' => $input['password'],
                'telefono' => $input['telefono'] ?? null,
                'sexo' => $input['sexo'],
                'estado_acceso' => 'HABILITADO',
                'activo' => true,
            ]);

            $postulante = Postulante::create([
                'id_usuario' => $user->id_usuario,
                'fecha_nacimiento' => $input['fecha_nacimiento'],
                'direccion' => $input['direccion'] ?? null,
                'colegio_procedencia' => $input['colegio_procedencia'] ?? null,
                'ciudad' => $input['ciudad'] ?? null,
                'documentacion_completa' => filter_var($input['documentacion_completa'], FILTER_VALIDATE_BOOL),
            ]);

            Postulacion::create([
                'id_postulante' => $postulante->id_postulante,
                'id_gestion' => $input['id_gestion'],
                'id_carrera_opcion1' => $input['id_carrera_opcion1'],
                'id_carrera_opcion2' => $input['id_carrera_opcion2'] ?? null,
                'estado_admision' => 'PENDIENTE',
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
