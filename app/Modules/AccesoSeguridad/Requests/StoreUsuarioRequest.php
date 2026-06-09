<?php

namespace App\Modules\AccesoSeguridad\Requests;

use App\Modules\AccesoSeguridad\Concerns\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isDocente = fn () => $this->input('tipo_usuario') === 'DOCENTE';
        $isPostulante = fn () => $this->input('tipo_usuario') === 'POSTULANTE';

        return [
            'tipo_usuario' => ['required', Rule::in(['POSTULANTE', 'DOCENTE', 'COORDINADOR_ACADEMICO', 'ADMINISTRADOR'])],
            'ci' => ['required', 'string', 'max:20', Rule::unique('usuario', 'ci')],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('usuario', 'username')],
            'correo' => ['required', 'email', 'max:150', Rule::unique('usuario', 'correo')],
            'password' => $this->passwordRules(),
            'telefono' => ['nullable', 'string', 'max:20'],
            'sexo' => ['required', Rule::in(['M', 'F', 'O'])],
            'estado_acceso' => ['nullable', Rule::in(['HABILITADO', 'BLOQUEADO', 'SUSPENDIDO'])],
            'activo' => ['nullable', 'boolean'],
            'maestria_educacion_superior' => [Rule::excludeIf(fn () => ! $isDocente()), 'required', 'boolean'],
            'contratado' => [Rule::excludeIf(fn () => ! $isDocente()), 'nullable', 'boolean'],
            'habilitaciones' => [Rule::excludeIf(fn () => ! $isDocente()), 'nullable', 'array'],
            'habilitaciones.PROFESIONAL_AREA' => [Rule::excludeIf(fn () => ! $isDocente()), 'nullable', 'array'],
            'habilitaciones.PROFESIONAL_AREA.*' => [Rule::excludeIf(fn () => ! $isDocente()), 'integer', Rule::exists('materia_cup', 'id_materia')],
            'habilitaciones.DIPLOMADO' => [Rule::excludeIf(fn () => ! $isDocente()), 'nullable', 'array'],
            'habilitaciones.DIPLOMADO.*' => [Rule::excludeIf(fn () => ! $isDocente()), 'integer', Rule::exists('materia_cup', 'id_materia')],
            'habilitaciones.MAESTRIA' => [Rule::excludeIf(fn () => ! $isDocente()), 'nullable', 'array'],
            'habilitaciones.MAESTRIA.*' => [Rule::excludeIf(fn () => ! $isDocente()), 'integer', Rule::exists('materia_cup', 'id_materia')],
            'fecha_nacimiento' => [Rule::excludeIf(fn () => ! $isPostulante()), 'required', 'date'],
            'direccion' => [Rule::excludeIf(fn () => ! $isPostulante()), 'nullable', 'string'],
            'colegio_procedencia' => [Rule::excludeIf(fn () => ! $isPostulante()), 'nullable', 'string', 'max:150'],
            'ciudad' => [Rule::excludeIf(fn () => ! $isPostulante()), 'nullable', 'string', 'max:80'],
            'documentacion_completa' => [Rule::excludeIf(fn () => ! $isPostulante()), 'nullable', 'boolean'],
            'id_gestion' => [Rule::excludeIf(fn () => ! $isPostulante()), 'required', 'integer', Rule::exists('gestion_academica', 'id_gestion')],
            'turno_preferido' => [Rule::excludeIf(fn () => ! $isPostulante()), 'required', Rule::in(['MANANA', 'TARDE', 'NOCHE'])],
            'id_carrera_opcion1' => [Rule::excludeIf(fn () => ! $isPostulante()), 'required', 'integer', Rule::exists('carrera', 'id_carrera')],
            'id_carrera_opcion2' => [Rule::excludeIf(fn () => ! $isPostulante()), 'nullable', 'integer', 'different:id_carrera_opcion1', Rule::exists('carrera', 'id_carrera')],
        ];
    }
}
