<?php

namespace App\Modules\GestionAcademica\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGestionAcademicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gestion = $this->route('gestion');

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('gestion_academica', 'nombre')->ignore($gestion?->id_gestion, 'id_gestion'),
            ],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
