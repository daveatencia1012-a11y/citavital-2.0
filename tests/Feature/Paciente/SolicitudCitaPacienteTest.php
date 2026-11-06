<?php

namespace Tests\Feature\Paciente;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SolicitudCitaPacienteTest extends PacientePortalTestCase
{
    public function test_solo_se_muestran_las_especialidades_activas(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000011']);
        $activa = Specialty::factory()->create(['name' => 'Cardiología', 'is_active' => true]);
        $inactiva = Specialty::factory()->create(['name' => 'Dermatología', 'is_active' => false]);

        $response = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.solicitar'));

        $response
            ->assertOk()
            ->assertSee($activa->name)
            ->assertDontSee($inactiva->name)
            ->assertSee('Se muestran únicamente las especialidades activas.');
    }

    public function test_solo_se_muestran_medicos_activos_de_la_especialidad(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000012']);
        $especialidad = Specialty::factory()->create(['name' => 'Pediatría', 'is_active' => true]);
        $otraEspecialidad = Specialty::factory()->create(['name' => 'Cardiología', 'is_active' => true]);

        $medicoActivo = Doctor::factory()->create([
            'first_name' => 'Andrea',
            'last_name' => 'Martínez',
            'is_active' => true,
        ]);
        $medicoActivo->specialties()->attach($especialidad);

        $medicoInactivo = Doctor::factory()->create([
            'first_name' => 'Carlos',
            'last_name' => 'Rojas',
            'is_active' => false,
        ]);
        $medicoInactivo->specialties()->attach($especialidad);

        $medicoOtraEspecialidad = Doctor::factory()->create([
            'first_name' => 'Laura',
            'last_name' => 'Gómez',
            'is_active' => true,
        ]);
        $medicoOtraEspecialidad->specialties()->attach($otraEspecialidad);

        $response = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.solicitar', ['specialty' => $especialidad->id]));

        $response
            ->assertOk()
            ->assertSee($medicoActivo->full_name)
            ->assertDontSee($medicoInactivo->full_name)
            ->assertDontSee($medicoOtraEspecialidad->full_name);
    }

    public function test_se_consultan_los_horarios_disponibles_segun_la_agenda(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000013']);
        $escenario = $this->crearAgendaDeJueves();
        $fecha = now()->addDay()->toDateString();

        app(AvailabilityService::class)->generateSlotsForDoctor($escenario['medico'], now()->addDay(), now()->addDay());

        $response = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.solicitar', [
                'specialty' => $escenario['especialidad']->id,
                'doctor' => $escenario['medico']->id,
                'date' => $fecha,
            ]));

        $response
            ->assertOk()
            ->assertSee('08:00')
            ->assertSee('08:30')
            ->assertSee('09:00')
            ->assertSee('11:30')
            ->assertSee('Continuar a confirmación');

        $this->assertDatabaseCount('appointment_slots', 8);

        $turnoOcupado = AppointmentSlot::query()
            ->where('doctor_id', $escenario['medico']->id)
            ->whereTime('starts_at', '09:00:00')
            ->firstOrFail();
        $turnoOcupado->update(['status' => SlotStatus::RESERVED]);

        AvailabilityBlock::query()->create([
            'doctor_id' => $escenario['medico']->id,
            'starts_at' => CarbonImmutable::parse($fecha.' 09:30:00', config('app.timezone')),
            'ends_at' => CarbonImmutable::parse($fecha.' 10:00:00', config('app.timezone')),
            'reason' => 'Descanso de prueba',
            'is_active' => true,
        ]);

        $responseActualizada = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.solicitar', [
                'specialty' => $escenario['especialidad']->id,
                'doctor' => $escenario['medico']->id,
                'date' => $fecha,
            ]));

        $responseActualizada
            ->assertOk()
            ->assertSee('08:00')
            ->assertDontSee('09:00')
            ->assertDontSee('09:30');
    }

    public function test_se_rechaza_un_turno_ocupado(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000014']);
        $escenario = $this->crearAgendaDeJueves();
        $turno = $this->crearTurno($escenario['medico'], now()->addDay()->setTime(8, 0), SlotStatus::RESERVED);

        $response = $this->actingAs($paciente, 'patient')->post(route('paciente.citas.store'), [
            'specialty_id' => $escenario['especialidad']->id,
            'doctor_id' => $escenario['medico']->id,
            'slot_id' => $turno->id,
            'date' => now()->addDay()->toDateString(),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('slot_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_se_rechaza_una_fecha_pasada(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000015']);
        $escenario = $this->crearAgendaDeJueves();
        $turno = $this->crearTurno($escenario['medico'], now()->subDay()->setTime(8, 0));

        $response = $this->actingAs($paciente, 'patient')->post(route('paciente.citas.store'), [
            'specialty_id' => $escenario['especialidad']->id,
            'doctor_id' => $escenario['medico']->id,
            'slot_id' => $turno->id,
            'date' => now()->subDay()->toDateString(),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_se_rechaza_un_turno_bloqueado(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000016']);
        $escenario = $this->crearAgendaDeJueves();
        $turno = $this->crearTurno($escenario['medico'], now()->addDay()->setTime(8, 0));

        AvailabilityBlock::query()->create([
            'doctor_id' => $escenario['medico']->id,
            'starts_at' => CarbonImmutable::parse(now()->addDay()->toDateString().' 07:30:00', config('app.timezone')),
            'ends_at' => CarbonImmutable::parse(now()->addDay()->toDateString().' 09:00:00', config('app.timezone')),
            'reason' => 'Vacaciones de prueba',
            'is_active' => true,
        ]);

        $response = $this->actingAs($paciente, 'patient')->post(route('paciente.citas.store'), [
            'specialty_id' => $escenario['especialidad']->id,
            'doctor_id' => $escenario['medico']->id,
            'slot_id' => $turno->id,
            'date' => now()->addDay()->toDateString(),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('slot_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_se_rechaza_una_especialidad_inactiva(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000023']);
        $especialidad = Specialty::factory()->create(['name' => 'Oftalmología', 'is_active' => false]);
        $medico = Doctor::factory()->create(['is_active' => true]);
        $medico->specialties()->attach($especialidad);
        $turno = $this->crearTurno($medico, now()->addDay()->setTime(8, 0));

        $response = $this->actingAs($paciente, 'patient')->post(route('paciente.citas.store'), [
            'specialty_id' => $especialidad->id,
            'doctor_id' => $medico->id,
            'slot_id' => $turno->id,
            'date' => now()->addDay()->toDateString(),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('specialty_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_se_rechaza_un_medico_inactivo(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000024']);
        $especialidad = Specialty::factory()->create(['name' => 'Neurología', 'is_active' => true]);
        $medico = Doctor::factory()->create(['is_active' => false]);
        $medico->specialties()->attach($especialidad);
        $turno = $this->crearTurno($medico, now()->addDay()->setTime(8, 0));

        $response = $this->actingAs($paciente, 'patient')->post(route('paciente.citas.store'), [
            'specialty_id' => $especialidad->id,
            'doctor_id' => $medico->id,
            'slot_id' => $turno->id,
            'date' => now()->addDay()->toDateString(),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('doctor_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_se_rechaza_un_medico_que_no_atiende_la_especialidad_elegida(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000025']);
        $especialidadElegida = Specialty::factory()->create(['name' => 'Cardiología', 'is_active' => true]);
        $especialidadDelMedico = Specialty::factory()->create(['name' => 'Pediatría', 'is_active' => true]);
        $medico = Doctor::factory()->create(['is_active' => true]);
        $medico->specialties()->attach($especialidadDelMedico);
        $turno = $this->crearTurno($medico, now()->addDay()->setTime(8, 0));

        $response = $this->actingAs($paciente, 'patient')->post(route('paciente.citas.store'), [
            'specialty_id' => $especialidadElegida->id,
            'doctor_id' => $medico->id,
            'slot_id' => $turno->id,
            'date' => now()->addDay()->toDateString(),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('doctor_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_no_se_ofrecen_turnos_que_ya_pasaron_en_el_dia_actual(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000026']);
        $especialidad = Specialty::factory()->create(['name' => 'Medicina general', 'is_active' => true]);
        $medico = Doctor::factory()->create(['is_active' => true]);
        $medico->specialties()->attach($especialidad);

        Availability::factory()->create([
            'doctor_id' => $medico->id,
            'weekday' => now()->dayOfWeekIso,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
            'is_active' => true,
        ]);

        app(AvailabilityService::class)->generateSlotsForDoctor($medico, now(), now());

        $response = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.solicitar', [
                'specialty' => $especialidad->id,
                'doctor' => $medico->id,
                'date' => now()->toDateString(),
            ]));

        $response
            ->assertOk()
            ->assertSee('10:30')
            ->assertSee('11:30')
            ->assertDontSee('08:00')
            ->assertDontSee('09:00');
    }

    public function test_la_reserva_exitosa_usa_el_paciente_de_la_sesion(): void
    {
        $paciente = $this->crearPaciente([
            'document_number' => '10000017',
            'first_name' => 'María',
            'last_name' => 'Cárdenas',
        ]);
        $otroPaciente = $this->crearPaciente([
            'document_number' => '10000018',
            'first_name' => 'Jorge',
            'last_name' => 'Beltrán',
        ]);
        $escenario = $this->crearAgendaDeJueves();
        $turno = $this->crearTurno($escenario['medico'], now()->addDay()->setTime(8, 0));

        $response = $this->actingAs($paciente, 'patient')->post(route('paciente.citas.store'), [
            'specialty_id' => $escenario['especialidad']->id,
            'doctor_id' => $escenario['medico']->id,
            'slot_id' => $turno->id,
            'date' => now()->addDay()->toDateString(),
            'patient_id' => $otroPaciente->id,
        ]);

        $cita = Appointment::query()->firstOrFail();

        $response
            ->assertRedirect(route('paciente.citas.show', $cita))
            ->assertSessionHas('status');

        $this->assertSame($paciente->id, $cita->patient_id);
        $this->assertNotSame($otroPaciente->id, $cita->patient_id);
        $this->assertSame(AppointmentStatus::SCHEDULED, $cita->status);
        $this->assertSame($turno->id, $cita->slot_id);
        $this->assertStringStartsWith('CV-', $cita->reference);
        $this->assertSame(SlotStatus::RESERVED, $turno->fresh()->status);
    }

    public function test_la_doble_reserva_del_mismo_turno_es_rechazada(): void
    {
        $pacienteA = $this->crearPaciente(['document_number' => '10000019']);
        $pacienteB = $this->crearPaciente(['document_number' => '10000020']);
        $escenario = $this->crearAgendaDeJueves();
        $turno = $this->crearTurno($escenario['medico'], now()->addDay()->setTime(8, 0));

        $datos = [
            'specialty_id' => $escenario['especialidad']->id,
            'doctor_id' => $escenario['medico']->id,
            'slot_id' => $turno->id,
            'date' => now()->addDay()->toDateString(),
        ];

        $this->actingAs($pacienteA, 'patient')
            ->post(route('paciente.citas.store'), $datos)
            ->assertRedirect();

        $this->actingAs($pacienteB, 'patient')
            ->post(route('paciente.citas.store'), $datos)
            ->assertSessionHasErrors('slot_id');

        $this->assertDatabaseCount('appointments', 1);
        $this->assertSame($pacienteA->id, Appointment::query()->firstOrFail()->patient_id);
    }

    public function test_una_reserva_conflictiva_simultanea_solo_permite_una_cita(): void
    {
        $pacienteA = $this->crearPaciente(['document_number' => '10000021']);
        $pacienteB = $this->crearPaciente(['document_number' => '10000022']);
        $escenario = $this->crearAgendaDeJueves();
        $turno = $this->crearTurno($escenario['medico'], now()->addDay()->setTime(8, 0));

        $servicio = app(AppointmentService::class);
        $servicio->book($turno, $pacienteA, $escenario['especialidad']);

        try {
            $servicio->book($turno->fresh(), $pacienteB, $escenario['especialidad']);
            $this->fail('La segunda reserva conflictiva debía ser rechazada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('slot', $exception->errors());
        }

        $this->assertSame(
            1,
            Appointment::query()->where('slot_id', $turno->id)->count(),
        );
        $this->assertSame($pacienteA->id, Appointment::query()->firstOrFail()->patient_id);
    }

    private function crearTurno(Doctor $doctor, Carbon $inicio, SlotStatus $estado = SlotStatus::AVAILABLE): AppointmentSlot
    {
        return AppointmentSlot::query()->create([
            'doctor_id' => $doctor->id,
            'starts_at' => $inicio->copy()->setTimezone(config('app.timezone')),
            'ends_at' => $inicio->copy()->setTimezone(config('app.timezone'))->addMinutes(30),
            'status' => $estado,
        ]);
    }
}
