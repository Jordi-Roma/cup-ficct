<?php

namespace App\Modules\Autenticacion\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_acceso' => ['required', Rule::in(['HABILITADO', 'BLOQUEADO', 'SUSPENDIDO'])],
            'activo' => ['required', 'boolean'],
        ];
    }
}
