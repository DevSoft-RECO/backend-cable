<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // Permitimos que cualquiera intente logearse
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'device_name' => ['required', 'string'], // Útil para identificar desde dónde se logean (ej: "Vue Web")
        ];
    }

    public function messages()
    {
        return [
            'email.exists' => 'Estas credenciales no coinciden con nuestros registros.',
        ];
    }
}
