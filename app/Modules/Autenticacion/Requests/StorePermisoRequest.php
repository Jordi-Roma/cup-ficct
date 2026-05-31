<?php

namespace App\Modules\Autenticacion\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('permiso', 'nombre')],
            'modulo' => ['required', 'string', 'max:50'],
            'accion' => ['required', 'string', 'max:50', Rule::in(['CREAR', 'LEER', 'ACTUALIZAR', 'ELIMINAR', 'EJECUTAR'])],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ];
    }
}
