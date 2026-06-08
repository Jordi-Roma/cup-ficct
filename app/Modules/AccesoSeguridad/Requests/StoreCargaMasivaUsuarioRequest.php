<?php

namespace App\Modules\AccesoSeguridad\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCargaMasivaUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_usuario' => ['required', Rule::in(['POSTULANTE', 'DOCENTE', 'COORDINADOR_ACADEMICO', 'ADMINISTRADOR'])],
            'archivo_csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
