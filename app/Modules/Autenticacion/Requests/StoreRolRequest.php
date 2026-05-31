<?php

namespace App\Modules\Autenticacion\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('rol', 'nombre')],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['integer', Rule::exists('permiso', 'id_permiso')],
        ];
    }
}
