<?php

namespace App\Modules\GestionAcademica\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMateriaCupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $materiaId = $this->route('materia')?->id_materia ?? $this->route('materia');

        return [
            'nombre' => ['required', 'string', 'max:80', Rule::unique('materia_cup', 'nombre')->ignore($materiaId, 'id_materia')],
            'activo' => ['nullable', 'boolean'],
        ];
    }
}
