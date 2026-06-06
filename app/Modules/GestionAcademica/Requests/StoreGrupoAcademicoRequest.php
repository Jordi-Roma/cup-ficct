<?php

namespace App\Modules\GestionAcademica\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGrupoAcademicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50'],
            'turno' => ['required', Rule::in(['MANANA', 'TARDE', 'NOCHE'])],
            'capacidad_maxima' => ['nullable', 'integer', 'min:1', 'max:70'],
        ];
    }
}
