<?php

namespace Tests\Feature\Administrativo;

use App\Enums\AppointmentStatus;
use App\Enums\InsuranceType;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PacienteAdministrativoTest extends TestCase
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

    public function test_un_administrador_puede_ver_el_listado_de_pacientes(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = $this->crearPaciente();

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.pacientes.index'));

        $response
            ->assertOk()
            ->assertSee('Listado de pacientes')
            ->assertSee($paciente->document_number)
            ->assertSee($paciente->first_name)
            ->assertSee($paciente->last_name)
            ->assertSee('36 años')
            ->assertSee('Contributivo')
            ->assertSee($paciente->primary_phone)
            ->assertSee('Activo')
            ->assertSee('Nuevo paciente');
    }

    public function test_el_formulario_de_registro_carga_con_csrf(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.pacientes.create'));

        $response
            ->assertOk()
            ->assertSee('Nuevo paciente')
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('administrativo.pacientes.store').'"', false)
            ->assertSee('Número de cédula')
            ->assertSee('Fecha de nacimiento')
            ->assertSee('Tipo de seguro')
            ->assertSee('Teléfono principal')
            ->assertSee('Teléfono secundario');
    }

    public function test_un_administrador_puede_registrar_un_paciente(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)->post(
            route('administrativo.pacientes.store'),
            $this->datosPaciente(['is_active' => false]),
        );

        $paciente = Patient::query()
            ->where('document_number', '12345678')
            ->firstOrFail();

        $response
            ->assertRedirect(route('administrativo.pacientes.show', $paciente))
            ->assertSessionHas('status', 'Paciente registrado correctamente.');

        $this->assertDatabaseHas('patients', [
            'id' => $paciente->id,
            'document_number' => '12345678',
            'first_name' => 'María',
            'last_name' => 'Cárdenas',
            'birth_date' => '1990-05-10',
            'insurance_type' => InsuranceType::CONTRIBUTIVE->value,
            'address' => 'Calle 10 # 5-25',
            'primary_phone' => '3101234567',
            'secondary_phone' => '6011234567',
            'is_active' => true,
        ]);
    }

    public function test_la_cedula_debe_tener_exactamente_8_o_10_digitos(): void
    {
        $administrador = $this->crearAdministrador();

        foreach (['1234567', '123456789', '1234567A'] as $documento) {
            $response = $this->actingAs($administrador)
                ->from(route('administrativo.pacientes.create'))
                ->post(route('administrativo.pacientes.store'), $this->datosPaciente([
                    'document_number' => $documento,
                ]));

            $response->assertSessionHasErrors('document_number');
        }

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_no_se_permite_registrar_una_cedula_duplicada(): void
    {
        $administrador = $this->crearAdministrador();
        $this->crearPaciente();

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.pacientes.create'))
            ->post(route('administrativo.pacientes.store'), $this->datosPaciente([
                'first_name' => 'Otra',
                'last_name' => 'Persona',
            ]));

        $response->assertSessionHasErrors('document_number');
        $this->assertDatabaseCount('patients', 1);
    }

    public function test_los_campos_obligatorios_son_validados_al_registrar(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.pacientes.create'))
            ->post(route('administrativo.pacientes.store'), []);

        $response->assertSessionHasErrors([
            'document_number',
            'first_name',
            'last_name',
            'birth_date',
            'insurance_type',
            'address',
            'primary_phone',
            'secondary_phone',
        ]);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_valida_fecha_de_nacimiento_y_formatos_telefonicos(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.pacientes.create'))
            ->post(route('administrativo.pacientes.store'), $this->datosPaciente([
                'birth_date' => '2027-01-01',
                'primary_phone' => '1234567890',
                'secondary_phone' => 'abc123',
            ]));

        $response->assertSessionHasErrors(['birth_date', 'primary_phone', 'secondary_phone']);
        $this->assertDatabaseCount('patients', 0);
    }

    public function test_un_administrador_puede_editar_un_paciente(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = $this->crearPaciente();

        $formulario = $this->actingAs($administrador)
            ->get(route('administrativo.pacientes.edit', $paciente));

        $formulario
            ->assertOk()
            ->assertSee('Editar paciente')
            ->assertSee($paciente->document_number)
            ->assertSee($paciente->first_name)
            ->assertSee('name="_method" value="PUT"', false);

        $response = $this->actingAs($administrador)->put(
            route('administrativo.pacientes.update', $paciente),
            $this->datosPaciente([
                'first_name' => 'María Fernanda',
                'address' => 'Carrera 8 # 12-40',
                'is_active' => false,
            ]),
        );

        $response
            ->assertRedirect(route('administrativo.pacientes.show', $paciente))
            ->assertSessionHas('status', 'Paciente actualizado correctamente.');

        $paciente->refresh();

        $this->assertSame('María Fernanda', $paciente->first_name);
        $this->assertSame('Carrera 8 # 12-40', $paciente->address);
        $this->assertTrue($paciente->is_active);
    }

    public function test_no_se_puede_editar_un_paciente_con_la_cedula_de_otro(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = $this->crearPaciente();
        $otroPaciente = $this->crearPaciente([
            'document_number' => '87654321',
            'first_name' => 'José',
            'last_name' => 'Pérez',
        ]);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.pacientes.edit', $paciente))
            ->put(route('administrativo.pacientes.update', $paciente), $this->datosPaciente([
                'document_number' => $otroPaciente->document_number,
            ]));

        $response->assertSessionHasErrors('document_number');
        $this->assertSame('12345678', $paciente->fresh()->document_number);
    }

    public function test_un_administrador_puede_desactivar_un_paciente_sin_eliminar_su_historial(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = $this->crearPaciente();
        $medico = Doctor::factory()->create();
        $especialidad = Specialty::factory()->create();

        $cita = $this->crearCita(
            $paciente,
            $medico,
            $especialidad,
            now()->copy()->addDay()->setTime(9, 0),
            AppointmentStatus::SCHEDULED,
            'CV-PACIENTE-HISTORIAL',
        );

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.pacientes.show', $paciente))
            ->patch(route('administrativo.pacientes.estado', $paciente));

        $response
            ->assertRedirect(route('administrativo.pacientes.show', $paciente))
            ->assertSessionHas('status', 'Paciente desactivado correctamente.');

        $this->assertFalse($paciente->fresh()->is_active);
        $this->assertDatabaseHas('patients', ['id' => $paciente->id]);
        $this->assertDatabaseHas('appointments', [
            'id' => $cita->id,
            'patient_id' => $paciente->id,
        ]);
    }

    public function test_un_administrador_puede_reactivar_un_paciente(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = $this->crearPaciente(['is_active' => false]);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.pacientes.show', $paciente))
            ->patch(route('administrativo.pacientes.estado', $paciente));

        $response
            ->assertRedirect(route('administrativo.pacientes.show', $paciente))
            ->assertSessionHas('status', 'Paciente reactivado correctamente.');

        $this->assertTrue($paciente->fresh()->is_active);
    }

    public function test_la_busqueda_filtra_por_cedula_nombres_y_apellidos(): void
    {
        $administrador = $this->crearAdministrador();

        $maria = $this->crearPaciente([
            'document_number' => '10000001',
            'first_name' => 'María',
            'last_name' => 'Cárdenas',
        ]);

        $jose = $this->crearPaciente([
            'document_number' => '10000002',
            'first_name' => 'José',
            'last_name' => 'Pérez',
        ]);

        $casos = [
            '10000001' => $maria,
            'María' => $maria,
            'Cárdenas' => $maria,
            'María Cárdenas' => $maria,
            'Pérez' => $jose,
        ];

        foreach ($casos as $termino => $esperado) {
            $response = $this->actingAs($administrador)->get(route('administrativo.pacientes.index', [
                'buscar' => $termino,
            ]));

            $response
                ->assertOk()
                ->assertViewHas('pacientes', function ($pacientes) use ($esperado): bool {
                    return $pacientes->total() === 1
                        && $pacientes->first()->is($esperado);
                });
        }
    }

    public function test_el_listado_pagina_los_pacientes(): void
    {
        $administrador = $this->crearAdministrador();
        Patient::factory()->count(11)->create();

        $primeraPagina = $this->actingAs($administrador)
            ->get(route('administrativo.pacientes.index'));

        $primeraPagina
            ->assertOk()
            ->assertViewHas('pacientes', function ($pacientes): bool {
                return $pacientes->total() === 11
                    && $pacientes->count() === 10
                    && $pacientes->currentPage() === 1;
            })
            ->assertSee('Mostrando 1–10 de 11')
            ->assertSee('Siguiente');

        $segundaPagina = $this->actingAs($administrador)
            ->get(route('administrativo.pacientes.index', ['page' => 2]));

        $segundaPagina
            ->assertOk()
            ->assertViewHas('pacientes', function ($pacientes): bool {
                return $pacientes->count() === 1
                    && $pacientes->currentPage() === 2;
            });
    }

    public function test_el_listado_muestra_un_mensaje_cuando_no_hay_pacientes(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.pacientes.index'));

        $response
            ->assertOk()
            ->assertViewHas('pacientes', fn ($pacientes): bool => $pacientes->total() === 0)
            ->assertSee('No hay pacientes registrados.')
            ->assertSee('Registrar paciente');
    }

    public function test_un_usuario_no_autenticado_no_puede_consultar_ni_modificar_pacientes(): void
    {
        $this->get(route('administrativo.pacientes.index'))
            ->assertRedirect(route('administrativo.login'));

        $this->post(route('administrativo.pacientes.store'), $this->datosPaciente())
            ->assertRedirect(route('administrativo.login'));

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_un_usuario_no_autorizado_recibe_403_en_pacientes(): void
    {
        $usuario = User::factory()->create([
            'email' => 'usuario.pacientes@uniajs.edu.co',
        ]);

        $this->actingAs($usuario)
            ->get(route('administrativo.pacientes.index'))
            ->assertForbidden();

        $this->actingAs($usuario)
            ->post(route('administrativo.pacientes.store'), $this->datosPaciente())
            ->assertForbidden();

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_un_administrador_puede_consultar_el_historial_de_citas_de_un_paciente(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = $this->crearPaciente();
        $medico = Doctor::factory()->create([
            'first_name' => 'Andrea',
            'last_name' => 'Martínez',
        ]);
        $especialidad = Specialty::factory()->create([
            'name' => 'Medicina General',
        ]);

        $fechaReciente = now()->copy()->subDay()->setTime(9, 0);
        $fechaAnterior = now()->copy()->subDays(2)->setTime(10, 30);

        $this->crearCita(
            $paciente,
            $medico,
            $especialidad,
            $fechaReciente,
            AppointmentStatus::SCHEDULED,
            'CV-HIST-001',
        );

        $this->crearCita(
            $paciente,
            $medico,
            $especialidad,
            $fechaAnterior,
            AppointmentStatus::MISSED,
            'CV-HIST-002',
        );

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.pacientes.show', $paciente));

        $response
            ->assertOk()
            ->assertViewHas('citas', fn ($citas): bool => $citas->total() === 2)
            ->assertSee('Historial de citas')
            ->assertSee($paciente->full_name)
            ->assertSee($paciente->document_number)
            ->assertSeeInOrder(['CV-HIST-001', 'CV-HIST-002'])
            ->assertSee($medico->full_name)
            ->assertSee('Medicina General')
            ->assertSee($fechaReciente->format('d/m/Y'))
            ->assertSee('09:00')
            ->assertSee('10:30')
            ->assertSee('Programada')
            ->assertSee('No asistió');
    }

    public function test_el_historial_muestra_un_mensaje_cuando_el_paciente_no_tiene_citas(): void
    {
        $administrador = $this->crearAdministrador();
        $paciente = $this->crearPaciente();

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.pacientes.show', $paciente));

        $response
            ->assertOk()
            ->assertViewHas('citas', fn ($citas): bool => $citas->total() === 0)
            ->assertSee('Este paciente aún no tiene citas registradas.');
    }

    private function crearAdministrador(): User
    {
        return User::factory()->create([
            'name' => 'Administrador de Prueba',
            'email' => config('citavital.admin_email'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearPaciente(array $overrides = []): Patient
    {
        return Patient::factory()->create($this->datosPaciente($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function datosPaciente(array $overrides = []): array
    {
        return array_merge([
            'document_number' => '12345678',
            'first_name' => 'María',
            'last_name' => 'Cárdenas',
            'birth_date' => '1990-05-10',
            'insurance_type' => InsuranceType::CONTRIBUTIVE->value,
            'address' => 'Calle 10 # 5-25',
            'primary_phone' => '3101234567',
            'secondary_phone' => '6011234567',
        ], $overrides);
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
}
