<?php

namespace Tests\Feature\Administrativo;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\SlotStatus;
use App\Models\AdministrativeAudit;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionCitasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('citavital_testing', config('database.connections.mysql.database'));
        $this->travelTo(now()->setDate(2026, 11, 4)->setTime(12, 0));
        $this->admin = User::factory()->create(['email' => config('citavital.admin_email')]);
        $this->actingAs($this->admin, 'web');
    }

    private function appointment(bool $future = false): Appointment
    {
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();
        $doctor->specialties()->attach($specialty);
        $start = $future ? now()->addDay()->setTime(9, 0) : now()->subDay()->setTime(9, 0);
        $slot = AppointmentSlot::query()->create(['doctor_id' => $doctor->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(30), 'status' => SlotStatus::RESERVED]);

        return Appointment::query()->create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'specialty_id' => $specialty->id,
            'slot_id' => $slot->id, 'scheduled_at' => $start, 'ends_at' => $start->copy()->addMinutes(30), 'status' => AppointmentStatus::SCHEDULED,
        ]);
    }

    private function newSlot(Appointment $appointment): AppointmentSlot
    {
        $start = now()->addDay()->setTime(10, 0);
        Availability::query()->firstOrCreate(['doctor_id' => $appointment->doctor_id, 'weekday' => $start->dayOfWeekIso], ['start_time' => '08:00', 'end_time' => '12:00', 'appointment_duration' => 30, 'is_active' => true]);

        return AppointmentSlot::query()->create(['doctor_id' => $appointment->doctor_id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(30), 'status' => SlotStatus::AVAILABLE]);
    }

    private function reschedule(Appointment $appointment, AppointmentSlot $slot)
    {
        return $this->post(route('administrativo.citas.reprogramar', $appointment), ['slot_id' => $slot->id, 'fecha' => $slot->starts_at->toDateString(), 'notes' => 'Cambio solicitado por teléfono.']);
    }

    private function missed(): Appointment
    {
        $appointment = $this->appointment();
        app(AppointmentService::class)->markMissed($appointment, $this->admin);

        return $appointment->fresh();
    }

    public function test_listado_paginado_y_ordenamiento(): void
    {
        $cita = $this->appointment();
        for ($i = 0; $i < 15; $i++) {
            $this->appointment(true);
        }
        $this->get(route('administrativo.citas.index'))->assertOk()->assertSee($cita->reference)->assertViewHas('citas', fn ($p) => $p->total() === 16 && $p->count() === 15);
        $this->get(route('administrativo.citas.index', ['page' => 2]))->assertOk()->assertViewHas('citas', fn ($p) => $p->count() === 1);
        $this->get(route('administrativo.citas.index', ['orden' => 'scheduled_at', 'direccion' => 'desc']))->assertOk()->assertViewHas('citas', fn ($p) => $p->first()->scheduled_at->isFuture());
    }

    public function test_busqueda_y_todos_los_filtros(): void
    {
        $cita = $this->appointment();
        $otra = $this->appointment(true);
        $filtros = ['buscar' => $cita->reference, 'paciente' => $cita->patient->full_name, 'cedula' => $cita->patient->document_number, 'medico' => $cita->doctor_id, 'especialidad' => $cita->specialty_id, 'fecha' => $cita->scheduled_at->toDateString(), 'estado' => 'PROGRAMADA'];
        foreach ($filtros as $key => $value) {
            $this->get(route('administrativo.citas.index', [$key => $value]))->assertOk()->assertSee($cita->reference);
        }
        $this->get(route('administrativo.citas.index', $filtros))->assertOk()->assertSee($cita->reference)->assertDontSee($otra->reference);
        $this->getJson(route('administrativo.citas.index', ['orden' => 'malicious']))->assertUnprocessable();
    }

    public function test_detalle_incluye_auditoria_y_seguimientos(): void
    {
        $cita = $this->missed();
        $this->get(route('administrativo.citas.show', $cita))->assertOk()->assertSee($cita->patient->full_name)->assertSee('MARK_MISSED')->assertSee($this->admin->name)->assertSee('Perdida – llamar al paciente');
    }

    public function test_registrar_asistencia_y_observacion_auditada(): void
    {
        $cita = $this->appointment();
        $this->patch(route('administrativo.citas.asistencia', $cita), ['notes' => 'Asistencia confirmada.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::COMPLETED, $cita->fresh()->status);
        $this->assertDatabaseHas('administrative_audits', ['actor_id' => $this->admin->id, 'entity_id' => $cita->id, 'operation' => 'COMPLETE', 'old_status' => 'PROGRAMADA', 'new_status' => 'REALIZADA', 'notes' => 'Asistencia confirmada.']);
    }

    public function test_no_permite_asistencia_ni_inasistencia_futuras(): void
    {
        $cita = $this->appointment(true);
        foreach (['asistencia', 'inasistencia'] as $action) {
            $this->patch(route('administrativo.citas.'.$action, $cita))->assertSessionHasErrors('appointment');
        }
        $this->assertSame(AppointmentStatus::SCHEDULED, $cita->fresh()->status);
        $this->assertDatabaseCount('appointment_follow_ups', 0);
        $this->assertDatabaseCount('administrative_audits', 0);
    }

    public function test_inasistencia_crea_seguimiento_unico(): void
    {
        $cita = $this->appointment();
        $this->patch(route('administrativo.citas.inasistencia', $cita))->assertSessionHasNoErrors();
        $this->patch(route('administrativo.citas.inasistencia', $cita))->assertSessionHasErrors('appointment');
        $this->assertSame(AppointmentStatus::MISSED, $cita->fresh()->status);
        $this->assertDatabaseCount('appointment_follow_ups', 1);
        $this->assertDatabaseHas('appointment_follow_ups', ['appointment_id' => $cita->id, 'status' => 'PENDIENTE_LLAMADA']);
        $this->assertDatabaseCount('administrative_audits', 1);
    }

    public function test_cancelacion_libera_turno_y_conserva_historial(): void
    {
        $cita = $this->appointment(true);
        $slot = $cita->slot;
        $this->patch(route('administrativo.citas.cancelar', $cita), ['notes' => 'Paciente solicita cancelación.'])->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::CANCELED, $cita->fresh()->status);
        $this->assertNull($cita->fresh()->slot_id);
        $this->assertSame(SlotStatus::AVAILABLE, $slot->fresh()->status);
        $this->assertDatabaseHas('administrative_audits', ['operation' => 'CANCEL', 'notes' => 'Paciente solicita cancelación.']);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_no_se_cancelan_citas_realizadas(): void
    {
        $cita = $this->appointment();
        app(AppointmentService::class)->complete($cita, $this->admin);
        $this->patch(route('administrativo.citas.cancelar', $cita))->assertSessionHasErrors('appointment');
        $this->assertSame(AppointmentStatus::COMPLETED, $cita->fresh()->status);
    }

    public function test_reprogramacion_enlaza_ambas_citas_y_audita(): void
    {
        $cita = $this->appointment(true);
        $old = $cita->slot;
        $new = $this->newSlot($cita);
        $this->reschedule($cita, $new)->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::RESCHEDULED, $cita->fresh()->status);
        $this->assertSame(SlotStatus::AVAILABLE, $old->fresh()->status);
        $this->assertSame(SlotStatus::RESERVED, $new->fresh()->status);
        $this->assertDatabaseHas('appointments', ['rescheduled_from_id' => $cita->id, 'slot_id' => $new->id, 'patient_id' => $cita->patient_id]);
        $this->assertDatabaseHas('administrative_audits', ['entity_id' => $cita->id, 'operation' => 'RESCHEDULE']);
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_no_se_reprograma_dos_veces_la_misma_cita(): void
    {
        $cita = $this->appointment(true);
        $slot = $this->newSlot($cita);
        $this->reschedule($cita, $slot)->assertSessionHasNoErrors();
        $this->reschedule($cita, $slot)->assertSessionHasErrors('appointment');
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_turno_ocupado_no_modifica_la_cita_original(): void
    {
        $cita = $this->appointment(true);
        $slot = $this->newSlot($cita);
        app(AppointmentService::class)->book($slot, Patient::factory()->create(), $cita->specialty);
        $this->reschedule($cita, $slot)->assertSessionHasErrors('slot');
        $this->assertSame(AppointmentStatus::SCHEDULED, $cita->fresh()->status);
        $this->assertSame(SlotStatus::RESERVED, $cita->slot->fresh()->status);
        $this->assertSame(1, Appointment::query()->where('slot_id', $slot->id)->count());
    }

    public function test_reprogramacion_rechaza_bloqueos(): void
    {
        $cita = $this->appointment(true);
        $slot = $this->newSlot($cita);
        AvailabilityBlock::query()->create(['doctor_id' => $cita->doctor_id, 'starts_at' => $slot->starts_at, 'ends_at' => $slot->ends_at, 'reason' => 'Ausencia', 'is_active' => true]);
        $this->reschedule($cita, $slot)->assertSessionHasErrors('slot');
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('administrative_audits', 0);
    }

    public function test_reprogramacion_rechaza_fechas_pasadas_y_turno_de_otro_medico(): void
    {
        $cita = $this->appointment();
        $this->reschedule($cita, $cita->slot)->assertSessionHasErrors('fecha');
        $otro = $this->appointment(true);
        $slot = $this->newSlot($otro);
        $this->reschedule($cita, $slot)->assertSessionHasErrors('slot');
    }

    public function test_reprogramacion_revalida_medico_especialidad_y_asociacion(): void
    {
        $cita = $this->appointment(true);
        $slot = $this->newSlot($cita);
        $cita->doctor->update(['is_active' => false]);
        $this->reschedule($cita, $slot)->assertSessionHasErrors('doctor');
        $cita->doctor->update(['is_active' => true]);
        $cita->specialty->update(['is_active' => false]);
        $this->reschedule($cita, $slot)->assertSessionHasErrors('specialty');
        $cita->specialty->update(['is_active' => true]);
        $cita->doctor->specialties()->detach();
        $this->reschedule($cita, $slot)->assertSessionHasErrors('specialty');
    }

    public function test_consulta_disponibilidad_por_post_y_get_sin_mutaciones(): void
    {
        $cita = $this->appointment(true);
        $slot = $this->newSlot($cita);
        $count = AppointmentSlot::query()->count();
        $this->get(route('administrativo.citas.edit', ['appointment' => $cita, 'fecha' => $slot->starts_at->toDateString()]))->assertOk()->assertSee('10:00');
        $this->assertDatabaseCount('appointment_slots', $count);
        $this->post(route('administrativo.citas.disponibilidad', $cita), ['fecha' => $slot->starts_at->toDateString()])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertGreaterThan($count, AppointmentSlot::query()->count());
        $this->get(route('administrativo.citas.cancelar', $cita))->assertStatus(405);
        $this->assertSame(AppointmentStatus::SCHEDULED, $cita->fresh()->status);
    }

    public function test_seguimientos_muestran_telefonos_y_filtran_pendientes(): void
    {
        $cita = $this->missed();
        $this->get(route('administrativo.seguimientos.index'))->assertOk()->assertSee('Perdida – llamar al paciente')->assertSee($cita->patient->primary_phone)->assertSee($cita->reference);
        $this->get(route('administrativo.seguimientos.index', ['estado' => 'CERRADO']))->assertOk()->assertDontSee($cita->reference);
    }

    public function test_intentos_contacto_observaciones_y_cierre_se_conservan(): void
    {
        $cita = $this->missed();
        $follow = $cita->followUps()->firstOrFail();
        foreach (['call', 'call', 'contact', 'note', 'close'] as $action) {
            $this->patch(route('administrativo.seguimientos.update', $follow), ['action' => $action, 'notes' => 'Observación '.$action])->assertSessionHasNoErrors();
        }
        $this->assertSame(FollowUpStatus::CLOSED, $follow->fresh()->status);
        $this->assertNotNull($follow->fresh()->contacted_at);
        $this->assertSame(2, AdministrativeAudit::query()->where('operation', 'FOLLOW_UP_CALL')->count());
        $this->assertStringContainsString('Observación contact', $follow->fresh()->notes);
        $this->patch(route('administrativo.seguimientos.update', $follow), ['action' => 'call', 'notes' => 'No válido'])->assertSessionHasErrors('action');
        $this->assertSame(6, AdministrativeAudit::query()->count());
    }

    public function test_inasistencia_reprogramada_conserva_estadistica_y_cierra_seguimiento(): void
    {
        $cita = $this->missed();
        $slot = $this->newSlot($cita);
        $this->reschedule($cita, $slot)->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::MISSED, $cita->fresh()->status);
        $this->assertSame(FollowUpStatus::RESCHEDULED, $cita->followUps()->firstOrFail()->status);
        $this->assertSame(1, $cita->rescheduledTo()->count());
        $this->reschedule($cita, $slot)->assertSessionHasErrors('appointment');
    }

    public function test_estadisticas_reflejan_operaciones_reales(): void
    {
        $service = app(AppointmentService::class);
        $service->complete($this->appointment(), $this->admin);
        $service->cancel($this->appointment(true), 'Prueba', $this->admin);
        $this->missed();
        $this->appointment(true);
        $summary = app(DashboardService::class)->summary();
        foreach (['scheduledAppointments', 'completedAppointments', 'canceledAppointments', 'missedAppointments'] as $metric) {
            $this->assertSame(1, $summary['metrics'][$metric]);
        }
        $this->assertCount(1, $summary['pendingFollowUps']);
        $this->get(route('administrativo.dashboard'))->assertOk();
    }

    public function test_seguridad_visitante_paciente_y_usuario_sin_permiso(): void
    {
        $cita = $this->appointment();
        auth('web')->logout();
        $this->get(route('administrativo.citas.index'))->assertRedirect(route('administrativo.login'));
        $this->patch(route('administrativo.citas.asistencia', $cita))->assertRedirect(route('administrativo.login'));
        $this->actingAs($cita->patient, 'patient')->get(route('administrativo.seguimientos.index'))->assertRedirect(route('administrativo.login'));
        $this->actingAs(User::factory()->create(), 'web')->patch(route('administrativo.citas.cancelar', $cita))->assertForbidden();
        $this->assertSame(AppointmentStatus::SCHEDULED, $cita->fresh()->status);
    }

    public function test_csrf_real_rechaza_sin_token_y_acepta_token_valido(): void
    {
        $cita = $this->appointment();
        // Activa la comprobación real del middleware, que Laravel omite en testing.
        $this->app['env'] = 'local';
        $this->patch(route('administrativo.citas.asistencia', $cita))->assertStatus(419);
        $this->assertSame(AppointmentStatus::SCHEDULED, $cita->fresh()->status);
        $this->withSession(['_token' => 'csrf-prueba'])->patch(route('administrativo.citas.asistencia', $cita), ['_token' => 'csrf-prueba'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(AppointmentStatus::COMPLETED, $cita->fresh()->status);
    }

    public function test_seguimiento_rechaza_reprogramacion_manual_sin_reserva(): void
    {
        $cita = $this->missed();
        $follow = $cita->followUps()->firstOrFail();
        $this->patch(route('administrativo.seguimientos.update', $follow), ['action' => 'reschedule', 'notes' => 'Inválido'])->assertSessionHasErrors('action');
        $this->assertSame(FollowUpStatus::PENDING_CALL, $follow->fresh()->status);
        $this->assertSame(0, $cita->rescheduledTo()->count());
    }
}
