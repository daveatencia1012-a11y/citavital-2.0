<?php

namespace App\Http\Requests\Administrativo;

use App\Models\User;
use App\Support\AdministratorAccess;
use Illuminate\Foundation\Http\FormRequest;

class OperacionCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && AdministratorAccess::allows($user);
    }

    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:2000']];
    }

    public function messages(): array
    {
        return ['notes.max' => 'La observación no debe superar 2000 caracteres.'];
    }
}
