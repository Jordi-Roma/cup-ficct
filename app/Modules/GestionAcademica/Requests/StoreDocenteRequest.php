<?php

namespace App\Modules\GestionAcademica\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', 'max:20', Rule::unique('usuario', 'ci')],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('usuario', 'username')],
            'correo' => ['required', 'email', 'max:150', Rule::unique('usuario', 'correo')],
            'password' => ['required', 'string', 'confirmed'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'sexo' => ['required', Rule::in(['M', 'F', 'O'])],
            'profesional_area' => ['required', 'boolean'],
            'maestria' => ['required', 'boolean'],
            'diplomado_educacion_superior' => ['required', 'boolean'],
            'contratado' => ['nullable', 'boolean'],
        ];
    }
}
