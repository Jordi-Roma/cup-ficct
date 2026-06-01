<?php

namespace App\Modules\Examenes\Requests;

class StoreNotaRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_postulacion' => ['required', 'exists:postulacion,id_postulacion'],
            'id_materia' => ['required', 'exists:materia_cup,id_materia'],
            'nro_examen' => ['required', 'integer', 'in:1,2,3'],
            'nota' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
