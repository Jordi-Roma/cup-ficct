<?php

namespace App\Modules\GestionAcademica\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'turno' => ['required', Rule::in(['MANANA', 'TARDE', 'NOCHE'])],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'activo' => ['nullable', 'boolean'],
        ];
    }
}
