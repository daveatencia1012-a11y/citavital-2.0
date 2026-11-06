<?php

namespace App\Http\Requests\Administrativo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBloqueoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')->where('is_active', true),
            ],
            'tipo' => ['required', Rule::in(['descanso', 'ausencia', 'vacaciones', 'bloqueo'])],
            'motivo' => ['nullable', 'string', 'max:150'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Selecciona un médico activo.',
            'doctor_id.exists' => 'El médico seleccionado no existe o está inactivo.',
            'tipo.required' => 'Selecciona el tipo de bloqueo.',
            'tipo.in' => 'El tipo de bloqueo seleccionado no es válido.',
            'motivo.max' => 'El detalle no puede superar los 150 caracteres.',
            'starts_at.required' => 'Ingresa la fecha y hora de inicio del bloqueo.',
            'starts_at.date' => 'La fecha y hora de inicio no es válida.',
            'ends_at.required' => 'Ingresa la fecha y hora de finalización del bloqueo.',
            'ends_at.date' => 'La fecha y hora de finalización no es válida.',
            'ends_at.after' => 'La fecha y hora final debe ser posterior al inicio.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'doctor_id' => 'médico',
            'tipo' => 'tipo de bloqueo',
            'motivo' => 'detalle',
            'starts_at' => 'inicio del bloqueo',
            'ends_at' => 'fin del bloqueo',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'motivo' => is_string($this->input('motivo'))
                ? trim($this->input('motivo'))
                : $this->input('motivo'),
        ]);
    }
}
