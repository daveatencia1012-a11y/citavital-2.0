<?php

namespace App\Http\Requests\Administrativo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisponibilidadRequest extends FormRequest
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
            'weekday' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'appointment_duration' => ['required', 'integer', 'between:5,240'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weekday.required' => 'Selecciona un día de la semana.',
            'weekday.between' => 'El día de la semana seleccionado no es válido.',
            'start_time.required' => 'Ingresa la hora de inicio.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'end_time.required' => 'Ingresa la hora de finalización.',
            'end_time.date_format' => 'La hora de finalización debe tener el formato HH:MM.',
            'appointment_duration.required' => 'Ingresa la duración de la consulta.',
            'appointment_duration.integer' => 'La duración de la consulta no es válida.',
            'appointment_duration.between' => 'La duración debe estar entre 5 y 240 minutos.',
            'is_active.boolean' => 'El estado de la franja no es válido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'weekday' => 'día de la semana',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de finalización',
            'appointment_duration' => 'duración de la consulta',
            'is_active' => 'estado',
        ];
    }
}
