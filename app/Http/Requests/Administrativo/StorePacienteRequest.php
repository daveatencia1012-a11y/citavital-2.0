<?php

namespace App\Http\Requests\Administrativo;

use App\Enums\InsuranceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePacienteRequest extends FormRequest
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
            'document_number' => [
                'required',
                'string',
                'regex:/^(?:\d{8}|\d{10})$/',
                'unique:patients,document_number',
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'insurance_type' => ['required', 'string', Rule::in(InsuranceType::values())],
            'address' => ['required', 'string', 'max:255'],
            'primary_phone' => ['required', 'string', 'regex:/^3\d{9}$/'],
            'secondary_phone' => ['required', 'string', 'regex:/^\d{7,10}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'Ingresa el número de cédula.',
            'document_number.regex' => 'La cédula debe contener exactamente 8 o 10 dígitos numéricos.',
            'document_number.unique' => 'Ya existe un paciente registrado con esta cédula.',
            'first_name.required' => 'Ingresa los nombres del paciente.',
            'first_name.max' => 'Los nombres no pueden superar los 100 caracteres.',
            'last_name.required' => 'Ingresa los apellidos del paciente.',
            'last_name.max' => 'Los apellidos no pueden superar los 100 caracteres.',
            'birth_date.required' => 'Ingresa la fecha de nacimiento.',
            'birth_date.date' => 'Ingresa una fecha de nacimiento válida.',
            'birth_date.before_or_equal' => 'La fecha de nacimiento no puede estar en el futuro.',
            'insurance_type.required' => 'Selecciona el tipo de seguro.',
            'insurance_type.in' => 'Selecciona un tipo de seguro válido.',
            'address.required' => 'Ingresa la dirección del paciente.',
            'address.max' => 'La dirección no puede superar los 255 caracteres.',
            'primary_phone.required' => 'Ingresa el teléfono principal.',
            'primary_phone.regex' => 'El teléfono principal debe tener 10 dígitos numéricos y comenzar por 3.',
            'secondary_phone.required' => 'Ingresa el teléfono secundario.',
            'secondary_phone.regex' => 'El teléfono secundario debe tener entre 7 y 10 dígitos numéricos.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_number' => 'cédula',
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'birth_date' => 'fecha de nacimiento',
            'insurance_type' => 'tipo de seguro',
            'address' => 'dirección',
            'primary_phone' => 'teléfono principal',
            'secondary_phone' => 'teléfono secundario',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->datosNormalizados());
    }

    /**
     * @return array<string, string|null>
     */
    private function datosNormalizados(): array
    {
        $datos = [];

        foreach ([
            'document_number',
            'first_name',
            'last_name',
            'birth_date',
            'insurance_type',
            'address',
            'primary_phone',
            'secondary_phone',
        ] as $campo) {
            $valor = $this->input($campo);

            $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
        }

        return $datos;
    }
}
