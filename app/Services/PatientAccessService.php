<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Validation\ValidationException;

/**
 * Punto único de verificación de identidad del portal del paciente.
 *
 * La demostración actual autoriza con cédula únicamente en el entorno local y
 * exclusivamente para documentos ficticios. Una fase posterior puede sustituir
 * este servicio por una verificación con código temporal sin cambiar el
 * controlador de acceso ni las rutas del portal.
 */
class PatientAccessService
{
    public function demoEnabled(): bool
    {
        return app()->environment('local')
            && (bool) config('citavital.patient_demo_login', false);
    }

    public function findDemoPatient(string $documentNumber): Patient
    {
        if (! $this->demoEnabled()) {
            throw ValidationException::withMessages([
                'document_number' => 'El acceso con cédula está habilitado únicamente en el entorno local de demostración.',
            ]);
        }

        if (! $this->isDemoDocument($documentNumber)) {
            throw ValidationException::withMessages([
                'document_number' => 'La cédula no corresponde a un paciente ficticio de la demostración.',
            ]);
        }

        $patient = Patient::query()
            ->where('document_number', $documentNumber)
            ->first();

        if ($patient === null) {
            throw ValidationException::withMessages([
                'document_number' => 'No existe un paciente registrado con esa cédula.',
            ]);
        }

        if (! $patient->is_active) {
            throw ValidationException::withMessages([
                'document_number' => 'El paciente se encuentra inactivo. Comunícate con el área administrativa.',
            ]);
        }

        return $patient;
    }

    private function isDemoDocument(string $documentNumber): bool
    {
        $prefixes = config('citavital.patient_demo_document_prefixes', []);

        foreach ((array) $prefixes as $prefix) {
            $prefix = trim((string) $prefix);

            if ($prefix !== '' && str_starts_with($documentNumber, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
