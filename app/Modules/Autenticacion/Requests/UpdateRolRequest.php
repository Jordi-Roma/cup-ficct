<?php

namespace App\Modules\Autenticacion\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rolId = $this->route('rol')?->id_rol ?? $this->route('rol');

        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('rol', 'nombre')->ignore($rolId, 'id_rol')],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['integer', Rule::exists('permiso', 'id_permiso')],
        ];
    }
}
