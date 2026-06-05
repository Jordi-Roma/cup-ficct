<?php

namespace App\Modules\GestionAcademica\Requests;

use App\Modules\GestionAcademica\Models\Aula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Aula|null $aula */
        $aula = $this->route('aula');

        return [
            'nombre' => [
                'required',
                'string',
                'max:80',
                Rule::unique('aula', 'nombre')->ignore($aula?->id_aula, 'id_aula'),
            ],
            'capacidad' => ['required', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
        ];
    }
}
