<?php

namespace Tests\Feature\Administrativo;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Models\Appointment;
use App\Models\AppointmentFollowUp;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardAdministrativoTest extends TestCase
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

    public function test_un_administrador_autenticado_puede_consultar_el_dashboard(): void
    {
        $administrador = $this->crearAdministrador();
        $fechaActual = Str::ucfirst(
            now(config('app.timezone'))
                ->locale('es')
                ->translatedFormat('l, j \d\e F \d\e Y')
        );

        $response = $this->actingAs($administrador)->get(route('administrativo.dashboard'));

        $response
            ->assertOk()
            ->assertSee('CitaVital')
            ->assertSee('Dashboard')
            ->assertSee($fechaActual)
            ->assertSee('Hora de Bogotá')
            ->assertSee($administrador->name)
            ->assertSee($administrador->email)
            ->assertSee('Sesión activa')
            ->assertSee('Cerrar sesión')
            ->assertSee('Pacientes registrados')
            ->assertSee('Médicos activos')
            ->assertSee('Citas programadas')
            ->assertSee('Citas realizadas')
            ->assertSee('Citas canceladas')
            ->assertSee('Citas con inasistencia')
            ->assertSee('Citas del día')
            ->assertSee('Próximamente');
    }

    public function test_un_usuario_no_autenticado_no_puede_acceder_al_dashboard(): void
    {
        $this->get(route('administrativo.dashboard'))
            ->assertRedirect(route('administrativo.login'));
    }

    public function test_un_usuario_no_autorizado_recibe_403(): void
    {
        $usuario = User::factory()->create([
            'email' => 'usuario.no.autorizado@uniajs.edu.co',
        ]);

        $this->actingAs($usuario)
            ->get(route('administrativo.dashboard'))
            ->assertForbidden();
    }

    public function test_los_indicadores_muestran_las_cantidades_correctas(): void
    {
        $administrador = $this->crearAdministrador();

        $pacientes = Patient::factory()->count(3)->create();
        $medicosActivos = Doctor::factory()->count(2)->create();
        Doctor::factory()->inactive()->create();
        $especialidad = Specialty::factory()->create();

        $paciente = $pacientes->first();
        $medico = $medicosActivos->first();

        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->setTime(8, 0), AppointmentStatus::SCHEDULED, 'CV-HOY-001');
        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->setTime(9, 0), AppointmentStatus::COMPLETED, 'CV-HOY-002');
        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->setTime(10, 0), AppointmentStatus::CANCELED, 'CV-HOY-003');
        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->addDay()->setTime(8, 0), AppointmentStatus::MISSED, 'CV-MANANA-001');
        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->addDay()->setTime(9, 0), AppointmentStatus::SCHEDULED, 'CV-MANANA-002');
        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->addDay()->setTime(10, 0), AppointmentStatus::RESCHEDULED, 'CV-MANANA-003');

        $response = $this->actingAs($administrador)->get(route('administrativo.dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('metrics.patients', 3)
            ->assertViewHas('metrics.activeDoctors', 2)
            ->assertViewHas('metrics.scheduledAppointments', 2)
            ->assertViewHas('metrics.completedAppointments', 1)
            ->assertViewHas('metrics.canceledAppointments', 1)
            ->assertViewHas('metrics.missedAppointments', 1)
            ->assertViewHas('metrics.todayAppointments', 3)
            ->assertSee('data-indicador="patients" data-valor="3"', false)
            ->assertSee('data-indicador="activeDoctors" data-valor="2"', false)
            ->assertSee('data-indicador="scheduledAppointments" data-valor="2"', false)
            ->assertSee('data-indicador="completedAppointments" data-valor="1"', false)
            ->assertSee('data-indicador="canceledAppointments" data-valor="1"', false)
            ->assertSee('data-indicador="missedAppointments" data-valor="1"', false)
            ->assertSee('data-indicador="todayAppointments" data-valor="3"', false);
    }

    public function test_las_citas_del_dia_corresponden_a_la_fecha_actual_y_se_ordenan_por_hora(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = Patient::factory()->create();
        $medico = Doctor::factory()->create();
        $especialidad = Specialty::factory()->create();

        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->setTime(9, 0), AppointmentStatus::SCHEDULED, 'CV-CITAS-HOY-02');
        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->setTime(8, 0), AppointmentStatus::SCHEDULED, 'CV-CITAS-HOY-01');
        $this->crearCita($paciente, $medico, $especialidad, now()->copy()->addDay()->setTime(8, 0), AppointmentStatus::SCHEDULED, 'CV-CITAS-MANANA');

        $response = $this->actingAs($administrador)->get(route('administrativo.dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('todayAppointments', fn ($citas): bool => $citas->count() === 2)
            ->assertSeeInOrder(['CV-CITAS-HOY-01', 'CV-CITAS-HOY-02'])
            ->assertDontSee('CV-CITAS-MANANA');
    }

    public function test_los_seguimientos_pendientes_muestran_solo_citas_no_asistidas_con_llamada_pendiente(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = Patient::factory()->create();
        $medico = Doctor::factory()->create();
        $especialidad = Specialty::factory()->create();

        $citaPendiente = $this->crearCita(
            $paciente,
            $medico,
            $especialidad,
            now()->copy()->subDay()->setTime(9, 0),
            AppointmentStatus::MISSED,
            'CV-SEGUIMIENTO-PENDIENTE',
        );

        $citaContactada = $this->crearCita(
            $paciente,
            $medico,
            $especialidad,
            now()->copy()->subDay()->setTime(10, 0),
            AppointmentStatus::MISSED,
            'CV-SEGUIMIENTO-CONTACTADO',
        );

        $citaProgramada = $this->crearCita(
            $paciente,
            $medico,
            $especialidad,
            now()->copy()->subDay()->setTime(11, 0),
            AppointmentStatus::SCHEDULED,
            'CV-SEGUIMIENTO-PROGRAMADA',
        );

        $this->crearSeguimiento($citaPendiente, FollowUpStatus::PENDING_CALL, 'Nota interna que no debe mostrarse.');
        $this->crearSeguimiento($citaContactada, FollowUpStatus::CONTACTED);
        $this->crearSeguimiento($citaProgramada, FollowUpStatus::PENDING_CALL);

        $response = $this->actingAs($administrador)->get(route('administrativo.dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('pendingFollowUps', function ($seguimientos): bool {
                return $seguimientos->count() === 1
                    && $seguimientos->first()->appointment->reference === 'CV-SEGUIMIENTO-PENDIENTE';
            })
            ->assertSee('Inasistencias por llamar')
            ->assertSee('Llamada pendiente')
            ->assertSee('CV-SEGUIMIENTO-PENDIENTE')
            ->assertSee($paciente->full_name)
            ->assertSee($medico->full_name)
            ->assertDontSee('CV-SEGUIMIENTO-CONTACTADO')
            ->assertDontSee('CV-SEGUIMIENTO-PROGRAMADA')
            ->assertDontSee('Nota interna que no debe mostrarse.');
    }

    public function test_el_dashboard_funciona_sin_datos_registrados(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)->get(route('administrativo.dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => collect($metrics)->every(fn (int $valor): bool => $valor === 0))
            ->assertViewHas('todayAppointments', fn ($citas): bool => $citas->isEmpty())
            ->assertViewHas('pendingFollowUps', fn ($seguimientos): bool => $seguimientos->isEmpty())
            ->assertSee('No hay citas programadas para hoy.')
            ->assertSee('No hay seguimientos pendientes.')
            ->assertSee('data-indicador="patients" data-valor="0"', false)
            ->assertSee('data-indicador="todayAppointments" data-valor="0"', false);
    }

    private function crearAdministrador(): User
    {
        return User::factory()->create([
            'name' => 'Administrador de Prueba',
            'email' => config('citavital.admin_email'),
        ]);
    }

    private function crearCita(
        Patient $paciente,
        Doctor $medico,
        Specialty $especialidad,
        Carbon $inicio,
        AppointmentStatus $estado,
        string $referencia,
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

    private function crearSeguimiento(
        Appointment $cita,
        FollowUpStatus $estado,
        ?string $notas = null,
    ): AppointmentFollowUp {
        return AppointmentFollowUp::query()->create([
            'appointment_id' => $cita->getKey(),
            'status' => $estado,
            'notes' => $notas,
        ]);
    }
}
