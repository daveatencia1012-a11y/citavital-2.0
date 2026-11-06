<?php

namespace Tests\Feature\Paciente;

use App\Enums\AppointmentStatus;

class CitasPacienteTest extends PacientePortalTestCase
{
    public function test_las_proximas_citas_se_muestran_en_orden_cronologico(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000031']);
        $escenario = $this->crearAgendaDeJueves();

        $primera = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->addDay()->setTime(8, 0),
            AppointmentStatus::SCHEDULED,
            'CV-PROXIMA-01',
        );

        $segunda = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->addDay()->setTime(10, 0),
            AppointmentStatus::SCHEDULED,
            'CV-PROXIMA-02',
        );

        $pasada = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->subDay()->setTime(9, 0),
            AppointmentStatus::COMPLETED,
            'CV-HISTORICA-01',
        );

        $response = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([$primera->reference, $segunda->reference])
            ->assertSee('Programada')
            ->assertDontSee($pasada->reference);
    }

    public function test_el_historial_muestra_citas_anteriores_y_permite_filtrar_por_fecha_y_estado(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000032']);
        $escenario = $this->crearAgendaDeJueves();

        $realizada = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->subDays(3)->setTime(9, 0),
            AppointmentStatus::COMPLETED,
            'CV-HIST-REALIZADA',
        );

        $cancelada = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->subDays(2)->setTime(10, 0),
            AppointmentStatus::CANCELED,
            'CV-HIST-CANCELADA',
        );

        $response = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.historial'));

        $response
            ->assertOk()
            ->assertSee($realizada->reference)
            ->assertSee($cancelada->reference)
            ->assertSee('Realizada')
            ->assertSee('Cancelada');

        $responseEstado = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.historial', ['status' => AppointmentStatus::COMPLETED->value]));

        $responseEstado
            ->assertOk()
            ->assertSee($realizada->reference)
            ->assertDontSee($cancelada->reference);

        $responseFecha = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.historial', ['date' => now()->subDays(2)->toDateString()]));

        $responseFecha
            ->assertOk()
            ->assertSee($cancelada->reference)
            ->assertDontSee($realizada->reference);
    }

    public function test_el_historial_usa_paginacion_y_conserva_la_trazabilidad_de_reprogramaciones(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000033']);
        $escenario = $this->crearAgendaDeJueves();

        $original = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->subDays(20)->setTime(8, 0),
            AppointmentStatus::RESCHEDULED,
            'CV-REPROGRAMADA-ORIGEN',
        );

        $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->subDays(19)->setTime(8, 0),
            AppointmentStatus::SCHEDULED,
            'CV-REPROGRAMADA-DESTINO',
        )->update(['rescheduled_from_id' => $original->id]);

        for ($indice = 1; $indice <= 8; $indice++) {
            $this->crearCita(
                $paciente,
                $escenario['medico'],
                $escenario['especialidad'],
                now()->subDays(30)->setTime(9, 0)->addMinutes($indice * 30),
                AppointmentStatus::COMPLETED,
                'CV-HIST-PAGINA-'.str_pad((string) $indice, 2, '0', STR_PAD_LEFT),
            );
        }

        $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.historial'))
            ->assertOk()
            ->assertSee('CV-REPROGRAMADA-DESTINO')
            ->assertSee('Reprogramada desde')
            ->assertSee($original->reference);

        $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.historial', ['page' => 2]))
            ->assertOk()
            ->assertSee('Mostrando 9–10 de 10');
    }

    public function test_el_detalle_de_una_cita_propia_es_accesible_y_el_ajeno_no(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000034']);
        $otroPaciente = $this->crearPaciente(['document_number' => '10000035']);
        $escenario = $this->crearAgendaDeJueves();

        $propia = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->addDay()->setTime(8, 0),
            AppointmentStatus::SCHEDULED,
            'CV-DETALLE-PROPIA',
        );

        $ajena = $this->crearCita(
            $otroPaciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->addDay()->setTime(9, 0),
            AppointmentStatus::SCHEDULED,
            'CV-DETALLE-AJENA',
        );

        $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.show', $propia))
            ->assertOk()
            ->assertSee($propia->reference)
            ->assertSee($escenario['medico']->full_name)
            ->assertSee($escenario['especialidad']->name)
            ->assertSee($paciente->full_name);

        $this->actingAs($paciente, 'patient')
            ->get(route('paciente.citas.show', $ajena))
            ->assertNotFound();
    }

    public function test_las_rutas_de_citas_requieren_una_sesion_de_paciente(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000036']);
        $escenario = $this->crearAgendaDeJueves();

        $cita = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->addDay()->setTime(8, 0),
            AppointmentStatus::SCHEDULED,
            'CV-RUTA-PROTEGIDA',
        );

        $rutas = [
            route('paciente.citas.index'),
            route('paciente.citas.solicitar'),
            route('paciente.citas.historial'),
            route('paciente.citas.show', $cita),
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertRedirect(route('paciente.login'));
        }
    }
}
