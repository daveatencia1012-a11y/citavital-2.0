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
use App\Services\DashboardService;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadisticaAdministrativaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('America/Bogota')->setDate(2026, 11, 15)->setTime(12, 0));
        $this->actingAs(User::factory()->create(['email' => config('citavital.admin_email')]), 'web');
    }

    private function cita(array $attributes = []): Appointment
    {
        return Appointment::query()->create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'specialty_id' => Specialty::factory()->create()->id,
            'scheduled_at' => now()->subDay(), 'ends_at' => now()->subDay()->addMinutes(30),
            'status' => AppointmentStatus::SCHEDULED, ...$attributes,
        ]);
    }

    private function summary(array $filters = []): array
    {
        return app(StatisticsService::class)->summary($filters);
    }

    public function test_administrador_accede_a_vista_y_graficos_accesibles(): void
    {
        $this->cita();
        $this->get(route('administrativo.estadisticas.index'))->assertOk()->assertSee('Histórico global')->assertSee('Ver datos en tabla accesible')->assertSee('scheduled_at')->assertSee('Resumen por especialidad');
    }

    public function test_usuario_no_autorizado_y_paciente_son_rechazados(): void
    {
        $this->actingAs(User::factory()->create(), 'web')->get(route('administrativo.estadisticas.index'))->assertForbidden();
        auth('web')->logout();
        $this->actingAs(Patient::factory()->create(), 'patient')->get(route('administrativo.estadisticas.index'))->assertRedirect(route('administrativo.login'));
    }

    public function test_visitante_sin_sesion_es_rechazado(): void
    {
        auth('web')->logout();
        $this->get(route('administrativo.estadisticas.index'))->assertRedirect(route('administrativo.login'));
    }

    public function test_total_de_registros_y_estados(): void
    {
        foreach (AppointmentStatus::cases() as $status) {
            $this->cita(['status' => $status]);
        }
        $summary = $this->summary();
        $this->assertSame(5, $summary['total']);
        foreach ($summary['states'] as $row) {
            $this->assertSame(1, $row['value']);
        }
        $this->assertSame(2, $summary['effective']);
    }

    public function test_especialidades_agrupadas_y_ordenadas(): void
    {
        $first = $this->cita();
        $this->cita(['specialty_id' => $first->specialty_id, 'status' => AppointmentStatus::COMPLETED]);
        $this->cita();
        $rows = $this->summary()['specialties'];
        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows->first()['value']);
        $this->assertSame(1, $rows->first()['REALIZADA']);
        $this->assertSame(1, $rows->first()['PROGRAMADA']);
    }

    public function test_medicos_agrupados_con_desglose(): void
    {
        $first = $this->cita();
        $this->cita(['doctor_id' => $first->doctor_id, 'status' => AppointmentStatus::COMPLETED]);
        $this->cita();
        $rows = $this->summary()['doctors'];
        $this->assertCount(2, $rows);
        $this->assertSame($first->doctor->full_name, $rows->first()['label']);
        $this->assertSame(2, $rows->first()['value']);
        $this->assertSame(1, $rows->first()['REALIZADA']);
    }

    public function test_rango_incluye_limites_del_dia_de_atencion(): void
    {
        foreach (['2026-11-01 00:00:00', '2026-11-02 23:59:59', '2026-10-31 23:59:59', '2026-11-03 00:00:00'] as $date) {
            $this->cita(['scheduled_at' => $date]);
        }
        $summary = $this->summary(['periodo' => 'personalizado', 'desde' => '2026-11-01', 'hasta' => '2026-11-02']);
        $this->assertSame(2, $summary['total']);
        $this->assertSame(4, $summary['history']->sum('value'));
        $this->assertSame(2, $summary['evolution']->sum('value'));
    }

    public function test_filtro_especialidad_afecta_tabla_y_graficos(): void
    {
        $first = $this->cita();
        $this->cita();
        $this->get(route('administrativo.estadisticas.index', ['especialidad' => $first->specialty_id]))->assertOk()->assertViewHas('total', 1)->assertViewHas('specialties', fn ($rows) => $rows->count() === 1)->assertViewHas('doctors', fn ($rows) => $rows->count() === 1);
    }

    public function test_filtro_medico_y_filtros_combinados(): void
    {
        $first = $this->cita();
        $second = $this->cita();
        $this->get(route('administrativo.estadisticas.index', ['medico' => $first->doctor_id]))->assertOk()->assertViewHas('total', 1);
        $this->get(route('administrativo.estadisticas.index', ['medico' => $first->doctor_id, 'especialidad' => $second->specialty_id]))->assertOk()->assertViewHas('total', 0);
    }

    public function test_fechas_y_filtros_invalidos_se_rechazan(): void
    {
        foreach ([
            ['periodo' => 'personalizado'],
            ['periodo' => 'personalizado', 'desde' => '2026-11-02', 'hasta' => '2026-11-01'],
            ['desde' => '2026-02-30'],
            ['hasta' => 'incorrecta'],
            ['periodo' => 'personalizado', 'desde' => '2000-01-01', 'hasta' => '2026-11-01'],
            ['periodo' => 'inventado'], ['medico' => '1 OR 1=1'], ['especialidad' => 99999],
        ] as $filters) {
            $this->getJson(route('administrativo.estadisticas.index', $filters))->assertUnprocessable();
        }
    }

    public function test_periodo_vacio_presenta_ceros_y_mensaje(): void
    {
        $this->get(route('administrativo.estadisticas.index'))->assertOk()->assertViewHas('total', 0)->assertSee('No hay datos para este período y filtros.');
        $this->assertSame(0, $this->summary()['evolution']->sum('value'));
    }

    public function test_reprogramaciones_no_duplican_atenciones_efectivas(): void
    {
        $old = $this->cita(['status' => AppointmentStatus::RESCHEDULED]);
        $new = $this->cita(['rescheduled_from_id' => $old->id, 'status' => AppointmentStatus::RESCHEDULED]);
        $this->cita(['rescheduled_from_id' => $new->id, 'status' => AppointmentStatus::COMPLETED]);
        $missed = $this->cita(['status' => AppointmentStatus::MISSED]);
        $this->cita(['rescheduled_from_id' => $missed->id]);
        $summary = $this->summary();
        $this->assertSame(5, $summary['total']);
        $this->assertSame(2, $summary['effective']);
        $this->assertSame(2, $summary['states']->firstWhere('status', 'REPROGRAMADA')['value']);
    }

    public function test_seguimientos_por_estado_y_fecha_de_la_cita_no_de_la_llamada(): void
    {
        $first = null;
        foreach (FollowUpStatus::cases() as $status) {
            $cita = $this->cita(['status' => AppointmentStatus::MISSED]);
            $first ??= $cita;
            AppointmentFollowUp::query()->create(['appointment_id' => $cita->id, 'status' => $status, 'created_at' => now()->subYear()]);
        }
        $summary = $this->summary();
        $this->assertSame(1, $summary['pending']);
        $this->assertSame(4, $summary['followUps']->first()['value']);
        foreach ($summary['followUps']->skip(1) as $row) {
            $this->assertSame(1, $row['value']);
        }
        $filtered = $this->summary(['medico' => $first->doctor_id]);
        $this->assertSame(2, $filtered['followUps']->sum('value'));
    }

    public function test_coherencia_con_dashboard_y_activos_globales(): void
    {
        foreach (AppointmentStatus::cases() as $status) {
            $this->cita(['status' => $status]);
        }
        $missed = Appointment::query()->where('status', 'NO_ASISTIO')->firstOrFail();
        AppointmentFollowUp::query()->create(['appointment_id' => $missed->id, 'status' => FollowUpStatus::PENDING_CALL]);
        $summary = $this->summary();
        $dashboard = app(DashboardService::class)->summary();
        foreach (['PROGRAMADA' => 'scheduledAppointments', 'REALIZADA' => 'completedAppointments', 'CANCELADA' => 'canceledAppointments', 'NO_ASISTIO' => 'missedAppointments'] as $status => $metric) {
            $this->assertSame($dashboard['metrics'][$metric], $summary['history']->firstWhere('status', $status)['value']);
        }
        $this->assertSame($dashboard['metrics']['activeDoctors'], $summary['activeDoctors']);
        $this->assertSame($dashboard['pendingFollowUps']->count(), $summary['pending']);
        $this->assertSame(Patient::query()->where('is_active', true)->count(), $summary['activePatients']);
    }

    public function test_periodos_rapidos_y_evolucion_mensual_con_ceros(): void
    {
        $this->assertSame('2026-11-15', $this->summary(['periodo' => 'hoy'])['desde']);
        $this->assertSame('2026-11-09', $this->summary(['periodo' => '7dias'])['desde']);
        $this->assertSame('2026-10-17', $this->summary(['periodo' => '30dias'])['desde']);
        $this->assertSame('2026-11-30', $this->summary(['periodo' => 'mes'])['hasta']);
        $this->cita();
        $summary = $this->summary(['periodo' => 'personalizado', 'desde' => '2026-01-01', 'hasta' => '2026-12-31']);
        $this->assertSame('mes', $summary['granularity']);
        $this->assertCount(12, $summary['evolution']);
        $this->assertSame(1, $summary['evolution']->sum('value'));
    }
}
