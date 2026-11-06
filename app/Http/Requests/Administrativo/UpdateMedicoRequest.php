<?php

namespace App\Http\Requests\Administrativo;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateMedicoRequest extends FormRequest
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
        /** @var Doctor|null $medico */
        $medico = $this->route('doctor');

        $codigo = mb_strtolower((string) $this->input('internal_code'));

        return [
            'internal_code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('doctors', 'internal_code')
                    ->where(fn ($query) => $query->whereRaw('LOWER(internal_code) = ?', [$codigo]))
                    ->ignore($medico),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'specialties' => ['required', 'array', 'min:1'],
            'specialties.*' => ['integer', 'distinct', Rule::exists('specialties', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'internal_code.required' => 'Ingresa el código interno del médico.',
            'internal_code.max' => 'El código interno no puede superar los 40 caracteres.',
            'internal_code.unique' => 'Ya existe un médico con ese código interno.',
            'first_name.required' => 'Ingresa los nombres del médico.',
            'first_name.max' => 'Los nombres no pueden superar los 100 caracteres.',
            'last_name.required' => 'Ingresa los apellidos del médico.',
            'last_name.max' => 'Los apellidos no pueden superar los 100 caracteres.',
            'specialties.required' => 'Selecciona al menos una especialidad.',
            'specialties.array' => 'Las especialidades seleccionadas no son válidas.',
            'specialties.min' => 'Selecciona al menos una especialidad.',
            'specialties.*.integer' => 'Una de las especialidades seleccionadas no es válida.',
            'specialties.*.distinct' => 'No se puede repetir una especialidad.',
            'specialties.*.exists' => 'Una de las especialidades seleccionadas no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'internal_code' => 'código interno',
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'specialties' => 'especialidades',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'internal_code' => is_string($this->input('internal_code'))
                ? Str::upper(trim($this->input('internal_code')))
                : $this->input('internal_code'),
            'first_name' => $this->normalizarEspacios($this->input('first_name')),
            'last_name' => $this->normalizarEspacios($this->input('last_name')),
        ]);
    }

    private function normalizarEspacios(mixed $valor): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        return trim((string) preg_replace('/\s+/u', ' ', $valor));
    }
}
