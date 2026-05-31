<?php

namespace App\Modules\GestionAcademica\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocenteRequest extends FormRequest
{
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
            'password' => ['nullable', 'string', 'confirmed'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'sexo' => ['required', Rule::in(['M', 'F', 'O'])],
            'estado_acceso' => ['required', Rule::in(['HABILITADO', 'BLOQUEADO', 'SUSPENDIDO'])],
            'usuario_activo' => ['required', 'boolean'],
            'profesional_area' => ['required', 'boolean'],
            'maestria' => ['required', 'boolean'],
            'diplomado_educacion_superior' => ['required', 'boolean'],
            'contratado' => ['required', 'boolean'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
