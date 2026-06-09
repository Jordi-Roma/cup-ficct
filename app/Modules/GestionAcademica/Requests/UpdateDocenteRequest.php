<?php

namespace App\Modules\GestionAcademica\Requests;

use App\Modules\AccesoSeguridad\Concerns\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocenteRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $docente = $this->route('docente');
        $usuarioId = $docente?->id_usuario;

        return [
            'ci' => ['required', 'string', 'max:20', Rule::unique('usuario', 'ci')->ignore($usuarioId, 'id_usuario')],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('usuario', 'username')->ignore($usuarioId, 'id_usuario')],
            'correo' => ['required', 'email', 'max:150', Rule::unique('usuario', 'correo')->ignore($usuarioId, 'id_usuario')],
            'password' => $this->optionalPasswordRules(),
            'telefono' => ['nullable', 'string', 'max:20'],
            'sexo' => ['required', Rule::in(['M', 'F', 'O'])],
            'estado_acceso' => ['required', Rule::in(['HABILITADO', 'BLOQUEADO', 'SUSPENDIDO'])],
            'usuario_activo' => ['required', 'boolean'],
            'profesional_area' => ['nullable', 'boolean'],
            'maestria' => ['nullable', 'boolean'],
            'diplomado_educacion_superior' => ['nullable', 'boolean'],
            'maestria_educacion_superior' => ['required', 'boolean'],
            'habilitaciones' => ['nullable', 'array'],
            'habilitaciones.PROFESIONAL_AREA' => ['nullable', 'array'],
            'habilitaciones.PROFESIONAL_AREA.*' => ['integer', Rule::exists('materia_cup', 'id_materia')],
            'habilitaciones.DIPLOMADO' => ['nullable', 'array'],
            'habilitaciones.DIPLOMADO.*' => ['integer', Rule::exists('materia_cup', 'id_materia')],
            'habilitaciones.MAESTRIA' => ['nullable', 'array'],
            'habilitaciones.MAESTRIA.*' => ['integer', Rule::exists('materia_cup', 'id_materia')],
            'contratado' => ['required', 'boolean'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
