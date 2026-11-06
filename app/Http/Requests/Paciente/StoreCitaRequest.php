<?php

namespace App\Http\Requests\Paciente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('patient')->check();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where('is_active', true),
            ],
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')->where('is_active', true),
            ],
            'slot_id' => [
                'required',
                'integer',
                Rule::exists('appointment_slots', 'id'),
            ],
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'specialty_id.required' => 'Selecciona una especialidad.',
            'specialty_id.exists' => 'La especialidad seleccionada no está disponible.',
            'doctor_id.required' => 'Selecciona un médico.',
            'doctor_id.exists' => 'El médico seleccionado no está disponible.',
            'slot_id.required' => 'Selecciona un horario disponible.',
            'slot_id.exists' => 'El horario seleccionado ya no existe. Elige otro.',
            'date.required' => 'Selecciona una fecha para la cita.',
            'date.date_format' => 'La fecha seleccionada no es válida.',
            'date.after_or_equal' => 'No se puede reservar una cita en una fecha pasada.',
        ];
    }
}
