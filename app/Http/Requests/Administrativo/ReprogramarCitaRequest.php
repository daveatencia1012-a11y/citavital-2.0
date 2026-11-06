<?php

namespace App\Http\Requests\Administrativo;

class ReprogramarCitaRequest extends OperacionCitaRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'fecha' => [$this->isMethod('get') ? 'nullable' : 'required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(90)->toDateString()],
            'slot_id' => [$this->routeIs('administrativo.citas.reprogramar') ? 'required' : 'nullable', 'integer', 'exists:appointment_slots,id'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'fecha.after_or_equal' => 'No se puede elegir una fecha pasada.',
            'fecha.before_or_equal' => 'Consulta una fecha dentro de los próximos 90 días.',
            'slot_id.required' => 'Selecciona un turno disponible.',
            'slot_id.exists' => 'El turno seleccionado no existe.',
        ];
    }
}
