<?php

namespace App\Http\Requests\Administrativo;

use App\Models\Specialty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEspecialidadRequest extends FormRequest
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
        /** @var Specialty|null $especialidad */
        $especialidad = $this->route('specialty');

        $nombre = mb_strtolower((string) $this->input('name'));

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('specialties', 'name')
                    ->where(fn ($query) => $query->whereRaw('LOWER(name) = ?', [$nombre]))
                    ->ignore($especialidad),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ingresa el nombre de la especialidad.',
            'name.string' => 'El nombre de la especialidad no es válido.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.unique' => 'Ya existe una especialidad con ese nombre.',
            'description.max' => 'La descripción no puede superar los 2000 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizarEspacios($this->input('name')),
            'description' => $this->limpiarTexto($this->input('description')),
        ]);
    }

    private function normalizarEspacios(mixed $valor): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        return trim((string) preg_replace('/\s+/u', ' ', $valor));
    }

    private function limpiarTexto(mixed $valor): mixed
    {
        return is_string($valor) ? trim($valor) : $valor;
    }
}
