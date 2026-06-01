<?php

namespace App\Modules\Examenes\Requests;

class UpdateNotaRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nota' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
