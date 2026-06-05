<?php

namespace App\Modules\AccesoSeguridad\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permisoId = $this->route('permiso')?->id_permiso ?? $this->route('permiso');

        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('permiso', 'nombre')->ignore($permisoId, 'id_permiso')],
            'modulo' => ['required', 'string', 'max:50'],
            'accion' => ['required', 'string', 'max:50', Rule::in(['CREAR', 'LEER', 'ACTUALIZAR', 'ELIMINAR', 'EJECUTAR'])],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ];
    }
}
