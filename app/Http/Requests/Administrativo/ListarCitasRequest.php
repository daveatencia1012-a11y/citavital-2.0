<?php

namespace App\Http\Requests\Administrativo;

use App\Enums\AppointmentStatus;
use Illuminate\Validation\Rule;

class ListarCitasRequest extends OperacionCitaRequest
{
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:120'],
            'paciente' => ['nullable', 'string', 'max:120'],
            'cedula' => ['nullable', 'string', 'max:10'],
            'medico' => ['nullable', 'integer', 'exists:doctors,id'],
            'especialidad' => ['nullable', 'integer', 'exists:specialties,id'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'orden' => ['nullable', Rule::in(['scheduled_at', 'reference', 'status'])],
            'direccion' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
