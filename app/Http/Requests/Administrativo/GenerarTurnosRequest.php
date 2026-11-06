<?php

namespace App\Http\Requests\Administrativo;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerarTurnosRequest extends FormRequest
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
            'desde' => ['required', 'date', 'after_or_equal:today'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
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
            'desde.required' => 'Ingresa la fecha inicial de generación.',
            'desde.date' => 'La fecha inicial no es válida.',
            'desde.after_or_equal' => 'No se pueden generar turnos en fechas pasadas.',
            'hasta.required' => 'Ingresa la fecha final de generación.',
            'hasta.date' => 'La fecha final no es válida.',
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'doctor_id' => 'médico',
            'desde' => 'fecha inicial',
            'hasta' => 'fecha final',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $desde = CarbonImmutable::parse((string) $this->input('desde'), config('app.timezone'))->startOfDay();
            $hasta = CarbonImmutable::parse((string) $this->input('hasta'), config('app.timezone'))->startOfDay();

            if ($desde->diffInDays($hasta) > 90) {
                $validator->errors()->add('hasta', 'El rango de generación no puede superar los 90 días.');
            }
        });
    }
}
