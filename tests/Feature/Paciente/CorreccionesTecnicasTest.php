<?php

namespace Tests\Feature\Paciente;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Models\AppointmentFollowUp;
use App\Services\AvailabilityService;
use App\Services\DashboardService;
use App\Services\StatisticsService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CorreccionesTecnicasTest extends PacientePortalTestCase
{
    public function test_gets_consultan_turnos_sin_escrituras_de_negocio(): void
    {
        $agenda = $this->crearAgendaDeJueves();
        $patient = $this->crearPaciente();
        $this->actingAs($patient, 'patient');
        $this->actingAs($this->crearAdministrador(), 'web');
        $cita = $this->crearCita($patient, $agenda['medico'], $agenda['especialidad'], now()->addDay(), AppointmentStatus::SCHEDULED);
        $urls = [
            route('paciente.citas.solicitar', ['specialty' => $agenda['especialidad']->id, 'doctor' => $agenda['medico']->id, 'date' => now()->addDay()->toDateString()]),
            route('administrativo.citas.edit', ['appointment' => $cita, 'fecha' => now()->addDay()->toDateString()]),
            route('administrativo.horarios.index'),
        ];
        foreach ([false, true] as $generate) {
            if ($generate) {
                app(AvailabilityService::class)->generateSlotsForDoctor($agenda['medico'], now()->addDay(), now()->addDay());
            }
            $before = $this->businessSnapshot();
            foreach ($urls as $url) {
                $this->get($url)->assertOk();
                $this->get($url)->assertOk();
            }
            $this->assertSame($before, $this->businessSnapshot());
            $this->assertCount($generate ? 8 : 0, app(AvailabilityService::class)->availableSlotsForDoctorOnDate($agenda['medico'], now()->addDay()));
        }
    }

    public function test_pendientes_comparten_criterio_y_respetan_filtros(): void
    {
        $agenda = $this->crearAgendaDeJueves();
        $patient = $this->crearPaciente();
        foreach ([AppointmentStatus::MISSED, AppointmentStatus::SCHEDULED, AppointmentStatus::COMPLETED] as $status) {
            $cita = $this->crearCita($patient, $agenda['medico'], $agenda['especialidad'], now()->subDay(), $status);
            AppointmentFollowUp::query()->create(['appointment_id' => $cita->id, 'status' => FollowUpStatus::PENDING_CALL]);
        }
        $summary = app(StatisticsService::class)->summary([]);
        $this->assertSame(1, $summary['pending']);
        $this->assertSame($summary['pending'], app(DashboardService::class)->summary()['pendingFollowUps']->count());
        $this->assertSame(1, $summary['followUps']->firstWhere('status', FollowUpStatus::PENDING_CALL->value)['value']);
        $this->assertSame(0, app(StatisticsService::class)->summary(['periodo' => 'hoy'])['pending']);
    }

    public function test_logout_admin_preserva_patient_con_csrf_y_rotacion(): void
    {
        $this->assertIsolatedLogout('web', 'patient', 'administrativo');
    }

    public function test_logout_patient_preserva_admin_con_csrf_y_rotacion(): void
    {
        $this->assertIsolatedLogout('patient', 'web', 'paciente');
    }

    public function test_mysql_aislado_y_ambos_triggers_instalados(): void
    {
        $this->assertSame('mysql', DB::getDriverName());
        $this->assertSame('citavital_testing', DB::selectOne('SELECT DATABASE() AS db')->db);
        $names = collect(DB::select('SHOW TRIGGERS'))->pluck('Trigger');
        $this->assertContains('trg_appointment_slots_no_overlap_bi', $names);
        $this->assertContains('trg_appointment_slots_no_overlap_bu', $names);
    }

    private function assertIsolatedLogout(string $closing, string $remaining, string $prefix): void
    {
        $this->withMiddleware(PreventRequestForgery::class);
        $patient = $this->crearPaciente();
        $admin = $this->crearAdministrador();
        Auth::guard('web')->login($admin);
        Auth::guard('patient')->login($patient);
        $this->withSession(['_token' => 'token-anterior']);
        $id = session()->getId();
        $this->post(route($prefix.'.logout'))->assertStatus(419);
        $this->assertAuthenticated('web');
        $this->assertAuthenticated('patient');
        $this->post(route($prefix.'.logout'), ['_token' => 'token-anterior'])->assertRedirect();
        $this->assertNotSame($id, session()->getId());
        $this->assertNotSame('token-anterior', session()->token());
        // Fuerza recuperar las identidades desde la sesión, no desde guards en memoria.
        Auth::forgetGuards();
        $this->assertGuest($closing);
        $this->assertAuthenticatedAs($remaining === 'web' ? $admin : $patient, $remaining);
        $this->get(route($prefix.'.dashboard'))->assertRedirect(route($prefix.'.login'));
        $otherPrefix = $remaining === 'web' ? 'administrativo' : 'paciente';
        $this->get(route($otherPrefix.'.dashboard'))->assertOk();
        $this->post(route($otherPrefix.'.logout'), ['_token' => 'token-anterior'])->assertStatus(419);
        $this->post(route($otherPrefix.'.logout'), ['_token' => session()->token()])->assertRedirect();
        Auth::forgetGuards();
        $this->assertGuest('web');
        $this->assertGuest('patient');
    }

    private function businessSnapshot(): array
    {
        $snapshot = [];
        foreach (['appointments', 'appointment_slots', 'availabilities', 'availability_blocks', 'appointment_follow_ups', 'administrative_audits'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        return $snapshot;
    }
}
