<?php

namespace App\Modules\GestionAcademica\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAsignacionAcademicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_grupo' => ['required', 'exists:grupo_academico,id_grupo'],
            'id_materia' => ['required', 'exists:materia_cup,id_materia'],
            'id_docente' => ['required', 'exists:docente,id_docente'],
            'id_aula' => ['required', 'exists:aula,id_aula'],
            'id_horario' => ['required', 'exists:horario,id_horario'],
            'activo' => ['nullable', 'boolean'],
        ];
    }
}
