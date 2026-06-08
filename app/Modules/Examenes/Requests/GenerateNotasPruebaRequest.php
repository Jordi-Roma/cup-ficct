<?php

namespace App\Modules\Examenes\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateNotasPruebaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_grupo' => in_array($this->input('id_grupo'), ['', 'all', null], true) ? null : $this->input('id_grupo'),
            'id_materia' => in_array($this->input('id_materia'), ['', 'all', null], true) ? null : $this->input('id_materia'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id_grupo' => ['nullable', 'exists:grupo_academico,id_grupo'],
            'id_materia' => ['nullable', 'exists:materia_cup,id_materia'],
            'nota_minima' => ['required', 'numeric', 'min:0', 'max:100'],
            'nota_maxima' => ['required', 'numeric', 'min:0', 'max:100', 'gte:nota_minima'],
        ];
    }
}
