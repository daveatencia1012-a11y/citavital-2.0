<?php

namespace App\Http\Requests\Paciente;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'regex:/^(?:[0-9]{8}|[0-9]{10})$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'Ingresa tu número de cédula.',
            'document_number.string' => 'La cédula debe contener solamente números.',
            'document_number.regex' => 'La cédula debe tener exactamente 8 o 10 dígitos y contener solamente números.',
        ];
    }
}
