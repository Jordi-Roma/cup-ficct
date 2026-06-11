<?php

namespace App\Modules\AccesoSeguridad\Requests;

use App\Modules\AccesoSeguridad\Concerns\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules {
        messages as passwordValidationMessages;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => $this->passwordRules(),
        ];
    }

    public function messages(): array
    {
        return $this->passwordValidationMessages();
    }
}
