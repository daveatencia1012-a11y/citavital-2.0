<?php

namespace Tests\Feature\Administrativo;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HorarioAdministrativoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_un_administrador_puede_ver_la_agenda_con_turnos_disponibles_reservados_bloqueados_y_pasados(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create([
            'internal_code' => 'MED-AGENDA',
            'first_name' => 'Andrea',
            'last_name' => 'Martínez',
        ]);
        $especialidad = Specialty::factory()->create(['name' => 'Cardiología']);
        $doctor->specialties()->attach($especialidad);
        $paciente = Patient::factory()->create(['first_name' => 'María', 'last_name' => 'Cárdenas']);

        Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 4,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        $disponible = $this->crearTurno($doctor, now()->addDay()->setTime(9, 0));
        $reservado = $this->crearTurno($doctor, now()->addDay()->setTime(9, 30));
        $this->crearCitaConTurno($paciente, $doctor, $especialidad, $reservado);

        $pasado = $this->crearTurno($doctor, now()->subDay()->setTime(9, 0));

        $bloqueado = $this->crearTurno($doctor, now()->addDays(2)->setTime(9, 0));
        $sinDisponibilidad = $this->crearTurno($doctor, now()->addDays(2)->setTime(11, 0));
        AvailabilityBlock::query()->create([
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDays(2)->setTime(8, 0),
            'ends_at' => now()->addDays(2)->setTime(10, 0),
            'reason' => 'Descanso · Reunión',
            'is_active' => true,
        ]);

        $response = $this->actingAs($administrador)->get(route('administrativo.horarios.index', [
            'doctor' => $doctor->id,
            'desde' => now()->subDays(2)->toDateString(),
            'hasta' => now()->addDays(2)->toDateString(),
        ]));

        $response
            ->assertOk()
            ->assertSee('Horarios')
            ->assertSee('Andrea Martínez')
            ->assertSee('Cardiología')
            ->assertSee('Disponibilidad semanal')
            ->assertSee('Jueves')
            ->assertSee('Bloqueos y ausencias')
            ->assertSee('Descanso · Reunión')
            ->assertSee('Disponible')
            ->assertSee('Reservado')
            ->assertSee('Bloqueado')
            ->assertSee('Horario pasado')
            ->assertSee('No disponible')
            ->assertSee($paciente->full_name)
            ->assertSee('09:00')
            ->assertSee('09:30')
            ->assertSee('11:00')
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertViewHas('turnos', function ($turnos) use ($disponible, $reservado, $pasado, $bloqueado, $sinDisponibilidad): bool {
                $estados = $turnos->mapWithKeys(fn (AppointmentSlot $turno): array => [
                    $turno->id => $turno->estado_visual,
                ]);

                return $turnos->count() === 5
                    && $estados[$disponible->id] === 'disponible'
                    && $estados[$reservado->id] === 'reservado'
                    && $estados[$pasado->id] === 'pasado'
                    && $estados[$bloqueado->id] === 'bloqueado'
                    && $estados[$sinDisponibilidad->id] === 'no_disponible';
            });
    }

    public function test_un_administrador_puede_crear_una_disponibilidad(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($administrador)->post(
            route('administrativo.horarios.disponibilidades.store'),
            [
                'doctor_id' => $doctor->id,
                'weekday' => 1,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'appointment_duration' => 30,
                'is_active' => '1',
            ],
        );

        $response
            ->assertRedirect(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->assertSessionHas('status', 'Franja de disponibilidad registrada correctamente.');

        $this->assertDatabaseHas('availabilities', [
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
            'is_active' => true,
        ]);
    }

    public function test_se_rechazan_horarios_superpuestos(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->post(route('administrativo.horarios.disponibilidades.store'), [
                'doctor_id' => $doctor->id,
                'weekday' => 1,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'appointment_duration' => 30,
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('availability');
        $this->assertDatabaseCount('availabilities', 1);
    }

    public function test_se_rechazan_duraciones_invalidas_y_horas_finales_anteriores(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();

        $duracionInvalida = $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->post(route('administrativo.horarios.disponibilidades.store'), [
                'doctor_id' => $doctor->id,
                'weekday' => 1,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'appointment_duration' => 7,
                'is_active' => '1',
            ]);

        $duracionInvalida->assertSessionHasErrors('appointment_duration');

        $horasInvertidas = $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->post(route('administrativo.horarios.disponibilidades.store'), [
                'doctor_id' => $doctor->id,
                'weekday' => 1,
                'start_time' => '12:00',
                'end_time' => '08:00',
                'appointment_duration' => 30,
                'is_active' => '1',
            ]);

        $horasInvertidas->assertSessionHasErrors('availability');
        $this->assertDatabaseCount('availabilities', 0);
    }

    public function test_un_administrador_puede_editar_desactivar_y_reactivar_una_disponibilidad(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        $disponibilidad = Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        $response = $this->actingAs($administrador)->put(
            route('administrativo.horarios.disponibilidades.update', $disponibilidad),
            [
                'weekday' => 2,
                'start_time' => '09:00',
                'end_time' => '13:00',
                'appointment_duration' => 60,
                'is_active' => '1',
            ],
        );

        $response
            ->assertRedirect(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->assertSessionHas('status', 'Franja de disponibilidad actualizada correctamente.');

        $disponibilidad->refresh();
        $this->assertSame(2, $disponibilidad->weekday);
        $this->assertSame('09:00:00', $disponibilidad->start_time);
        $this->assertSame('13:00:00', $disponibilidad->end_time);
        $this->assertSame(60, $disponibilidad->appointment_duration);

        $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->patch(route('administrativo.horarios.disponibilidades.estado', $disponibilidad))
            ->assertSessionHas('status', 'Franja de disponibilidad desactivada correctamente.');

        $this->assertFalse($disponibilidad->fresh()->is_active);

        $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->patch(route('administrativo.horarios.disponibilidades.estado', $disponibilidad))
            ->assertSessionHas('status', 'Franja de disponibilidad reactivada correctamente.');

        $this->assertTrue($disponibilidad->fresh()->is_active);
    }

    public function test_un_administrador_puede_generar_turnos(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        $lunes = now()->next('Monday')->startOfDay();

        $response = $this->actingAs($administrador)->post(
            route('administrativo.horarios.generar'),
            [
                'doctor_id' => $doctor->id,
                'desde' => $lunes->toDateString(),
                'hasta' => $lunes->toDateString(),
            ],
        );

        $response
            ->assertRedirect(route('administrativo.horarios.index', [
                'doctor' => $doctor->id,
                'desde' => $lunes->toDateString(),
                'hasta' => $lunes->toDateString(),
            ]))
            ->assertSessionHas('status', 'Generación completada. Se crearon 8 turnos nuevos en el rango seleccionado.');

        $this->assertSame(8, AppointmentSlot::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('starts_at', [$lunes->copy()->startOfDay(), $lunes->copy()->endOfDay()])
            ->count());

        $this->assertDatabaseHas('appointment_slots', [
            'doctor_id' => $doctor->id,
            'starts_at' => $lunes->copy()->setTime(8, 0)->format('Y-m-d H:i:s'),
            'ends_at' => $lunes->copy()->setTime(8, 30)->format('Y-m-d H:i:s'),
            'status' => SlotStatus::AVAILABLE->value,
        ]);
    }

    public function test_la_generacion_de_turnos_es_idempotente(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        $lunes = now()->next('Monday')->startOfDay();
        $datos = [
            'doctor_id' => $doctor->id,
            'desde' => $lunes->toDateString(),
            'hasta' => $lunes->toDateString(),
        ];

        $this->actingAs($administrador)
            ->post(route('administrativo.horarios.generar'), $datos)
            ->assertSessionHas('status', 'Generación completada. Se crearon 8 turnos nuevos en el rango seleccionado.');

        $this->actingAs($administrador)
            ->post(route('administrativo.horarios.generar'), $datos)
            ->assertSessionHas('status', 'No se crearon turnos nuevos: la agenda ya estaba generada para ese rango.');

        $this->assertSame(8, AppointmentSlot::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('starts_at', [$lunes->copy()->startOfDay(), $lunes->copy()->endOfDay()])
            ->count());
    }

    public function test_se_registran_bloqueos_y_no_se_generan_turnos_durante_ellos(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        $lunes = now()->next('Monday')->startOfDay();

        $bloqueo = $this->actingAs($administrador)->post(
            route('administrativo.horarios.bloqueos.store'),
            [
                'doctor_id' => $doctor->id,
                'tipo' => 'descanso',
                'motivo' => 'Reunión',
                'starts_at' => $lunes->copy()->setTime(9, 0)->format('Y-m-d\TH:i'),
                'ends_at' => $lunes->copy()->setTime(10, 0)->format('Y-m-d\TH:i'),
            ],
        );

        $bloqueo
            ->assertRedirect(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->assertSessionHas('status', 'Bloqueo de agenda registrado correctamente.');

        $this->assertDatabaseHas('availability_blocks', [
            'doctor_id' => $doctor->id,
            'reason' => 'Descanso · Reunión',
            'is_active' => true,
        ]);

        $generacion = $this->actingAs($administrador)->post(
            route('administrativo.horarios.generar'),
            [
                'doctor_id' => $doctor->id,
                'desde' => $lunes->toDateString(),
                'hasta' => $lunes->toDateString(),
            ],
        );

        $generacion->assertSessionHas('status', 'Generación completada. Se crearon 6 turnos nuevos en el rango seleccionado.');

        $this->assertSame(6, AppointmentSlot::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('starts_at', [$lunes->copy()->startOfDay(), $lunes->copy()->endOfDay()])
            ->count());

        $this->assertDatabaseMissing('appointment_slots', [
            'doctor_id' => $doctor->id,
            'starts_at' => $lunes->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseMissing('appointment_slots', [
            'doctor_id' => $doctor->id,
            'starts_at' => $lunes->copy()->setTime(9, 30)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_no_se_puede_crear_un_bloqueo_que_afecte_una_cita_futura(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        $paciente = Patient::factory()->create();
        $especialidad = Specialty::factory()->create();

        $lunes = now()->next('Monday')->startOfDay();
        $turno = $this->crearTurno($doctor, $lunes->copy()->setTime(9, 0));
        $cita = $this->crearCitaConTurno($paciente, $doctor, $especialidad, $turno);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->post(route('administrativo.horarios.bloqueos.store'), [
                'doctor_id' => $doctor->id,
                'tipo' => 'ausencia',
                'motivo' => 'Cita externa',
                'starts_at' => $lunes->copy()->setTime(8, 30)->format('Y-m-d\TH:i'),
                'ends_at' => $lunes->copy()->setTime(10, 0)->format('Y-m-d\TH:i'),
            ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('availability_blocks', 0);
        $this->assertDatabaseHas('appointments', [
            'id' => $cita->id,
            'slot_id' => $turno->id,
            'status' => AppointmentStatus::SCHEDULED->value,
        ]);
    }

    public function test_no_se_puede_desactivar_una_disponibilidad_que_deja_citas_futuras_sin_cobertura(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        $paciente = Patient::factory()->create();
        $especialidad = Specialty::factory()->create();
        $disponibilidad = Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        $lunes = now()->next('Monday')->startOfDay();
        $turno = $this->crearTurno($doctor, $lunes->copy()->setTime(9, 0));
        $cita = $this->crearCitaConTurno($paciente, $doctor, $especialidad, $turno);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->patch(route('administrativo.horarios.disponibilidades.estado', $disponibilidad));

        $response->assertSessionHasErrors('availability');

        $this->assertTrue($disponibilidad->fresh()->is_active);
        $this->assertDatabaseHas('appointments', [
            'id' => $cita->id,
            'status' => AppointmentStatus::SCHEDULED->value,
        ]);
    }

    public function test_no_se_puede_eliminar_una_disponibilidad_con_citas_futuras(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        $paciente = Patient::factory()->create();
        $especialidad = Specialty::factory()->create();
        $disponibilidad = Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        $lunes = now()->next('Monday')->startOfDay();
        $turno = $this->crearTurno($doctor, $lunes->copy()->setTime(9, 0));
        $cita = $this->crearCitaConTurno($paciente, $doctor, $especialidad, $turno);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->delete(route('administrativo.horarios.disponibilidades.destroy', $disponibilidad));

        $response->assertSessionHasErrors('availability');

        $this->assertDatabaseHas('availabilities', ['id' => $disponibilidad->id]);
        $this->assertDatabaseHas('appointments', ['id' => $cita->id]);
    }

    public function test_no_se_puede_modificar_una_disponibilidad_que_deja_citas_sin_cobertura(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        $paciente = Patient::factory()->create();
        $especialidad = Specialty::factory()->create();
        $disponibilidad = Availability::factory()->create([
            'doctor_id' => $doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        $lunes = now()->next('Monday')->startOfDay();
        $turno = $this->crearTurno($doctor, $lunes->copy()->setTime(9, 0));
        $cita = $this->crearCitaConTurno($paciente, $doctor, $especialidad, $turno);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->put(route('administrativo.horarios.disponibilidades.update', $disponibilidad), [
                'weekday' => 2,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'appointment_duration' => 30,
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('availability');

        $disponibilidad->refresh();
        $this->assertSame(1, $disponibilidad->weekday);
        $this->assertDatabaseHas('appointments', [
            'id' => $cita->id,
            'status' => AppointmentStatus::SCHEDULED->value,
        ]);
    }

    public function test_no_se_permite_generar_turnos_en_fechas_pasadas(): void
    {
        $administrador = $this->crearAdministrador();
        $doctor = Doctor::factory()->create();
        Availability::factory()->create(['doctor_id' => $doctor->id]);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.horarios.index', ['doctor' => $doctor->id]))
            ->post(route('administrativo.horarios.generar'), [
                'doctor_id' => $doctor->id,
                'desde' => now()->subDay()->toDateString(),
                'hasta' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('desde');
        $this->assertSame(0, AppointmentSlot::query()->where('doctor_id', $doctor->id)->count());
    }

    private function crearAdministrador(): User
    {
        return User::factory()->create([
            'name' => 'Administrador de Prueba',
            'email' => config('citavital.admin_email'),
        ]);
    }

    private function crearTurno(Doctor $doctor, Carbon $inicio): AppointmentSlot
    {
        return AppointmentSlot::query()->create([
            'doctor_id' => $doctor->id,
            'starts_at' => $inicio,
            'ends_at' => $inicio->copy()->addMinutes(30),
            'status' => SlotStatus::AVAILABLE,
        ]);
    }

    private function crearCitaConTurno(
        Patient $paciente,
        Doctor $doctor,
        Specialty $especialidad,
        AppointmentSlot $turno,
    ): Appointment {
        $turno->update(['status' => SlotStatus::RESERVED]);

        return Appointment::query()->create([
            'patient_id' => $paciente->id,
            'doctor_id' => $doctor->id,
            'specialty_id' => $especialidad->id,
            'slot_id' => $turno->id,
            'scheduled_at' => $turno->starts_at,
            'ends_at' => $turno->ends_at,
            'status' => AppointmentStatus::SCHEDULED,
        ]);
    }
}
