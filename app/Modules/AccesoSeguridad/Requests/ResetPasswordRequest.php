<?php

namespace App\Modules\AccesoSeguridad\Requests;

use App\Modules\AccesoSeguridad\Concerns\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'username_or_email' => ['required', 'string', 'max:150'],
            'password' => $this->passwordRules(),
        ];
    }
}
