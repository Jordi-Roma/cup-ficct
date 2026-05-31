<?php

namespace App\Modules\RegistroPostulantes\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostulanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $postulante = $this->route('postulante');
        $usuarioId = $postulante?->id_usuario;

        return [
            'direccion' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('usuario', 'correo')->ignore($usuarioId, 'id_usuario')],
            'colegio_procedencia' => ['nullable', 'string', 'max:150'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'fecha_nacimiento' => ['required', 'date'],
            'documentacion_completa' => ['required', 'boolean'],
            'id_carrera_opcion1' => ['required', 'integer', Rule::exists('carrera', 'id_carrera')],
            'id_carrera_opcion2' => ['nullable', 'integer', Rule::exists('carrera', 'id_carrera'), 'different:id_carrera_opcion1'],
        ];
    }
}
