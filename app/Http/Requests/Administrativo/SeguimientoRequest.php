<?php

namespace App\Http\Requests\Administrativo;

use App\Enums\FollowUpStatus;
use Illuminate\Validation\Rule;

class SeguimientoRequest extends OperacionCitaRequest
{
    public function rules(): array
    {
        if ($this->isMethod('get')) {
            return ['estado' => ['nullable', Rule::in(['todos', ...array_column(FollowUpStatus::cases(), 'value')])]];
        }

        return [
            'action' => ['required', Rule::in(['call', 'contact', 'note', 'close'])],
            'notes' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [...parent::messages(), 'notes.required' => 'Registra una observación sobre la gestión realizada.'];
    }
}
