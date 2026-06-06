<?php

namespace App\Modules\AccesoSeguridad\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_usuario' => ['required', Rule::in(['POSTULANTE', 'DOCENTE', 'COORDINADOR_ACADEMICO', 'ADMINISTRADOR'])],
            'ci' => ['required', 'string', 'max:20', Rule::unique('usuario', 'ci')],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('usuario', 'username')],
            'correo' => ['required', 'email', 'max:150', Rule::unique('usuario', 'correo')],
            'password' => ['required', 'string', 'confirmed'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'sexo' => ['required', Rule::in(['M', 'F', 'O'])],
            'estado_acceso' => ['nullable', Rule::in(['HABILITADO', 'BLOQUEADO', 'SUSPENDIDO'])],
            'activo' => ['nullable', 'boolean'],
            'maestria_educacion_superior' => ['required_if:tipo_usuario,DOCENTE', 'boolean'],
            'contratado' => ['nullable', 'boolean'],
            'habilitaciones' => ['nullable', 'array'],
            'habilitaciones.PROFESIONAL_AREA' => ['nullable', 'array'],
            'habilitaciones.PROFESIONAL_AREA.*' => ['integer', Rule::exists('materia_cup', 'id_materia')],
            'habilitaciones.DIPLOMADO' => ['nullable', 'array'],
            'habilitaciones.DIPLOMADO.*' => ['integer', Rule::exists('materia_cup', 'id_materia')],
            'habilitaciones.MAESTRIA' => ['nullable', 'array'],
            'habilitaciones.MAESTRIA.*' => ['integer', Rule::exists('materia_cup', 'id_materia')],
            'fecha_nacimiento' => ['required_if:tipo_usuario,POSTULANTE', 'date'],
            'direccion' => ['nullable', 'string'],
            'colegio_procedencia' => ['nullable', 'string', 'max:150'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'documentacion_completa' => ['nullable', 'boolean'],
            'id_gestion' => ['required_if:tipo_usuario,POSTULANTE', 'integer', Rule::exists('gestion_academica', 'id_gestion')],
            'turno_preferido' => ['required_if:tipo_usuario,POSTULANTE', Rule::in(['MANANA', 'TARDE', 'NOCHE'])],
            'id_carrera_opcion1' => ['required_if:tipo_usuario,POSTULANTE', 'integer', Rule::exists('carrera', 'id_carrera')],
            'id_carrera_opcion2' => ['nullable', 'integer', 'different:id_carrera_opcion1', Rule::exists('carrera', 'id_carrera')],
        ];
    }
}
