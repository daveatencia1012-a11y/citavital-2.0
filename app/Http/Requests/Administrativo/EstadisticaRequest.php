<?php

namespace App\Http\Requests\Administrativo;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EstadisticaRequest extends OperacionCitaRequest
{
    public function rules(): array
    {
        return [
            'periodo' => ['nullable', Rule::in(['hoy', '7dias', '30dias', 'mes', 'personalizado'])],
            'desde' => ['required_if:periodo,personalizado', 'nullable', 'date_format:Y-m-d'],
            'hasta' => ['required_if:periodo,personalizado', 'nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'especialidad' => ['nullable', 'integer', 'exists:specialties,id'],
            'medico' => ['nullable', 'integer', 'exists:doctors,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'desde.required_if' => 'Indica la fecha inicial del rango personalizado.',
            'hasta.required_if' => 'Indica la fecha final del rango personalizado.',
            'desde.date_format' => 'La fecha inicial debe tener formato AAAA-MM-DD.',
            'hasta.date_format' => 'La fecha final debe tener formato AAAA-MM-DD.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'periodo.in' => 'Selecciona un período válido.',
            'especialidad.exists' => 'La especialidad no existe.',
            'medico.exists' => 'El médico no existe.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->any() && $this->filled(['desde', 'hasta'])
                && CarbonImmutable::parse($this->input('desde'))->diffInDays(CarbonImmutable::parse($this->input('hasta'))) > 1826) {
                $validator->errors()->add('hasta', 'El rango no debe superar cinco años.');
            }
        }];
    }
}
