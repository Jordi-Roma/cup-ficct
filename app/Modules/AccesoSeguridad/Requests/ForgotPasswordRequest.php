<?php

namespace App\Modules\AccesoSeguridad\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username_or_email' => ['required', 'string', 'max:150'],
        ];
    }
}
