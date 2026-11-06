<?php

namespace Tests\Feature\Paciente;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

abstract class PacientePortalTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', config('app.timezone')));
        $this->habilitarDemoLocal();

        // El entorno simulado es local para habilitar la demostración; el
        // middleware CSRF se omite en las pruebas de flujo, pero el formulario
        // conserva y valida su token en el entorno real.
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function habilitarDemoLocal(): void
    {
        $this->app['env'] = 'local';
        config(['citavital.patient_demo_login' => true]);
    }

    protected function crearPaciente(array $attributes = []): Patient
    {
        $attributes = array_merge([
            'document_number' => '100000'.str_pad((string) (Patient::query()->count() + 1), 2, '0', STR_PAD_LEFT),
            'first_name' => 'María',
            'last_name' => 'Cárdenas',
            'is_active' => true,
        ], $attributes);

        return Patient::factory()->create($attributes);
    }

    protected function crearAdministrador(): User
    {
        return User::factory()->create([
            'name' => 'Administrador de Prueba',
            'email' => config('citavital.admin_email'),
        ]);
    }

    /**
     * @return array{especialidad: Specialty, medico: Doctor, disponibilidad: Availability}
     */
    protected function crearAgendaDeJueves(): array
    {
        $especialidad = Specialty::factory()->create([
            'name' => 'Cardiología',
            'description' => 'Diagnóstico y seguimiento cardiovascular.',
        ]);

        $medico = Doctor::factory()->create([
            'internal_code' => 'MED-PACIENTE',
            'first_name' => 'Andrea',
            'last_name' => 'Martínez',
            'is_active' => true,
        ]);

        $medico->specialties()->attach($especialidad);

        $disponibilidad = Availability::factory()->create([
            'doctor_id' => $medico->getKey(),
            'weekday' => 4,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
            'is_active' => true,
        ]);

        return [
            'especialidad' => $especialidad,
            'medico' => $medico,
            'disponibilidad' => $disponibilidad,
        ];
    }

    protected function crearCita(
        Patient $paciente,
        Doctor $medico,
        Specialty $especialidad,
        Carbon $inicio,
        AppointmentStatus $estado = AppointmentStatus::SCHEDULED,
        ?string $referencia = null,
    ): Appointment {
        $inicio = $inicio->copy()->setTimezone(config('app.timezone'));

        return Appointment::query()->create([
            'reference' => $referencia,
            'patient_id' => $paciente->getKey(),
            'doctor_id' => $medico->getKey(),
            'specialty_id' => $especialidad->getKey(),
            'slot_id' => null,
            'scheduled_at' => $inicio,
            'ends_at' => $inicio->copy()->addMinutes(30),
            'status' => $estado,
            'canceled_at' => $estado === AppointmentStatus::CANCELED ? now() : null,
        ]);
    }
}
