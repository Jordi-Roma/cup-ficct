<?php

namespace App\Modules\GestionAcademica\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCupoCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_carrera' => ['required', 'exists:carrera,id_carrera'],
            'id_gestion' => ['required', 'exists:gestion_academica,id_gestion'],
            'cupo_maximo' => ['required', 'integer', 'min:1'],
        ];
    }
}
