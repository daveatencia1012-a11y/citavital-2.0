<?php

namespace Tests\Feature\Paciente;

use App\Enums\AppointmentStatus;
use App\Models\Patient;
use Illuminate\Support\Facades\RateLimiter;

class AutenticacionPacienteTest extends PacientePortalTestCase
{
    public function test_el_formulario_de_acceso_carga_con_csrf_y_accion_segura(): void
    {
        $response = $this->get(route('paciente.login'));

        $response
            ->assertOk()
            ->assertSee('Portal del paciente')
            ->assertSee('Número de cédula')
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('paciente.login.store').'"', false)
            ->assertSee('Modo demostración local');
    }

    public function test_la_cedula_es_obligatoria_y_solo_admite_ocho_o_diez_digitos(): void
    {
        $valoresInvalidos = ['', '1234567', '1000000A', '100000011'];

        foreach ($valoresInvalidos as $valor) {
            $this->from(route('paciente.login'))
                ->post(route('paciente.login.store'), ['document_number' => $valor])
                ->assertRedirect(route('paciente.login'))
                ->assertSessionHasErrors('document_number');

            $this->assertGuest('patient');
        }
    }

    public function test_un_paciente_inexistente_no_puede_ingresar(): void
    {
        $response = $this
            ->from(route('paciente.login'))
            ->post(route('paciente.login.store'), ['document_number' => '10000099']);

        $response
            ->assertRedirect(route('paciente.login'))
            ->assertSessionHasErrors([
                'document_number' => 'No existe un paciente registrado con esa cédula.',
            ]);

        $this->assertGuest('patient');
    }

    public function test_un_paciente_inactivo_no_puede_ingresar(): void
    {
        $paciente = $this->crearPaciente([
            'document_number' => '10000077',
            'is_active' => false,
        ]);

        $response = $this
            ->from(route('paciente.login'))
            ->post(route('paciente.login.store'), [
                'document_number' => $paciente->document_number,
            ]);

        $response
            ->assertRedirect(route('paciente.login'))
            ->assertSessionHasErrors([
                'document_number' => 'El paciente se encuentra inactivo. Comunícate con el área administrativa.',
            ]);

        $this->assertGuest('patient');
    }

    public function test_el_acceso_demostrativo_local_autentica_al_paciente_y_regenera_la_sesion(): void
    {
        $paciente = $this->crearPaciente([
            'document_number' => '10000001',
            'first_name' => 'María',
            'last_name' => 'Cárdenas',
        ]);

        $this->startSession();
        $sesionAnterior = $this->app['session']->getId();

        $response = $this->post(route('paciente.login.store'), [
            'document_number' => $paciente->document_number,
        ]);

        $response->assertRedirect(route('paciente.dashboard'));

        $this->assertAuthenticatedAs($paciente, 'patient');
        $this->assertGuest('web');
        $this->assertNotSame($sesionAnterior, $this->app['session']->getId());
        $this->assertSame(0, RateLimiter::attempts('paciente-login|10000001|127.0.0.1'));
    }

    public function test_el_acceso_con_cedula_esta_bloqueado_fuera_del_entorno_local(): void
    {
        $this->app['env'] = 'testing';
        config(['citavital.patient_demo_login' => true]);

        $paciente = $this->crearPaciente(['document_number' => '10000002']);

        $response = $this
            ->from(route('paciente.login'))
            ->post(route('paciente.login.store'), [
                'document_number' => $paciente->document_number,
            ]);

        $response
            ->assertRedirect(route('paciente.login'))
            ->assertSessionHasErrors([
                'document_number' => 'El acceso con cédula está habilitado únicamente en el entorno local de demostración.',
            ]);

        $this->assertGuest('patient');
    }

    public function test_el_acceso_rechaza_cedulas_que_no_pertenecen_a_datos_ficticios(): void
    {
        $paciente = Patient::factory()->create([
            'document_number' => '12345678',
            'is_active' => true,
        ]);

        $response = $this
            ->from(route('paciente.login'))
            ->post(route('paciente.login.store'), [
                'document_number' => $paciente->document_number,
            ]);

        $response
            ->assertRedirect(route('paciente.login'))
            ->assertSessionHasErrors([
                'document_number' => 'La cédula no corresponde a un paciente ficticio de la demostración.',
            ]);

        $this->assertGuest('patient');
    }

    public function test_el_acceso_aplica_limitacion_de_intentos(): void
    {
        for ($intento = 0; $intento < 5; $intento++) {
            $this->from(route('paciente.login'))
                ->post(route('paciente.login.store'), ['document_number' => '10000099'])
                ->assertSessionHasErrors('document_number');
        }

        $response = $this
            ->from(route('paciente.login'))
            ->post(route('paciente.login.store'), ['document_number' => '10000099']);

        $response
            ->assertRedirect(route('paciente.login'))
            ->assertSessionHasErrors('document_number');

        $this->assertStringContainsString(
            'Demasiados intentos fallidos',
            (string) $response->getSession()->get('errors')->first('document_number'),
        );

        $this->assertGuest('patient');
    }

    public function test_las_sesiones_de_paciente_y_administrador_permanecen_aisladas(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000003']);
        $administrador = $this->crearAdministrador();

        $this->post(route('paciente.login.store'), [
            'document_number' => $paciente->document_number,
        ])->assertRedirect(route('paciente.dashboard'));

        $this->assertAuthenticatedAs($paciente, 'patient');
        $this->assertGuest('web');

        $this->actingAs($administrador, 'web');

        $this->assertAuthenticatedAs($administrador, 'web');
        $this->assertAuthenticatedAs($paciente, 'patient');
    }

    public function test_un_administrador_no_asume_la_identidad_de_un_paciente(): void
    {
        $administrador = $this->crearAdministrador();

        $this->actingAs($administrador, 'web')
            ->get(route('paciente.dashboard'))
            ->assertRedirect(route('paciente.login'));

        $this->assertAuthenticatedAs($administrador, 'web');
        $this->assertGuest('patient');
    }

    public function test_un_paciente_no_puede_acceder_al_area_administrativa(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000004']);

        $this->actingAs($paciente, 'patient')
            ->get(route('administrativo.dashboard'))
            ->assertRedirect(route('administrativo.login'));
    }

    public function test_el_paciente_puede_cerrar_sesion_sin_cerrar_la_sesion_administrativa(): void
    {
        $paciente = $this->crearPaciente(['document_number' => '10000005']);
        $administrador = $this->crearAdministrador();

        $this->actingAs($paciente, 'patient');
        $this->actingAs($administrador, 'web');

        $this->post(route('paciente.logout'))
            ->assertRedirect(route('paciente.login'))
            ->assertSessionHas('status', 'Sesión del paciente cerrada correctamente.');

        $this->assertGuest('patient');
        $this->assertAuthenticatedAs($administrador, 'web');

        $this->get(route('paciente.dashboard'))
            ->assertRedirect(route('paciente.login'));
    }

    public function test_el_dashboard_del_paciente_muestra_solo_su_informacion(): void
    {
        $paciente = $this->crearPaciente([
            'document_number' => '10000006',
            'first_name' => 'María',
            'last_name' => 'Cárdenas',
        ]);
        $otroPaciente = $this->crearPaciente([
            'document_number' => '10000007',
            'first_name' => 'Jorge',
            'last_name' => 'Beltrán',
        ]);
        $escenario = $this->crearAgendaDeJueves();

        $proxima = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->addDay()->setTime(9, 0),
            AppointmentStatus::SCHEDULED,
            'CV-PACIENTE-FUTURA',
        );

        $anterior = $this->crearCita(
            $paciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->subDay()->setTime(9, 0),
            AppointmentStatus::COMPLETED,
            'CV-PACIENTE-ANTERIOR',
        );

        $ajena = $this->crearCita(
            $otroPaciente,
            $escenario['medico'],
            $escenario['especialidad'],
            now()->addDay()->setTime(10, 0),
            AppointmentStatus::SCHEDULED,
            'CV-PACIENTE-AJENA',
        );

        $response = $this->actingAs($paciente, 'patient')
            ->get(route('paciente.dashboard'));

        $response
            ->assertOk()
            ->assertSee('Bienvenido, '.$paciente->full_name)
            ->assertSee('Solicitar cita')
            ->assertSee('Próximas citas')
            ->assertSee('Citas pendientes')
            ->assertSee('Historial')
            ->assertSee('Cerrar sesión')
            ->assertSee($proxima->reference)
            ->assertSee($anterior->reference)
            ->assertSee('Programada')
            ->assertSee('Realizada')
            ->assertDontSee($ajena->reference)
            ->assertDontSee($otroPaciente->full_name);
    }
}
