<?php

namespace Tests\Feature\Administrativo;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MedicoAdministrativoTest extends TestCase
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

    public function test_un_administrador_puede_ver_el_listado_de_medicos(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create(['name' => 'Cardiología']);
        $medico = Doctor::factory()->create([
            'internal_code' => 'MED-900',
            'first_name' => 'Andrea',
            'last_name' => 'Martínez',
        ]);
        $medico->specialties()->attach($especialidad);

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.index'));

        $response
            ->assertOk()
            ->assertSee('Listado de médicos')
            ->assertSee('MED-900')
            ->assertSee('Andrea Martínez')
            ->assertSee('Cardiología')
            ->assertSee('Activo')
            ->assertSee('Nuevo médico');
    }

    public function test_el_formulario_de_registro_carga_con_csrf_y_especialidades(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create(['name' => 'Pediatría']);

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.create'));

        $response
            ->assertOk()
            ->assertSee('Nuevo médico')
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('administrativo.medicos.store').'"', false)
            ->assertSee('name="specialties[]"', false)
            ->assertSee($especialidad->name)
            ->assertSee('Código interno')
            ->assertSee('Nombres')
            ->assertSee('Apellidos');
    }

    public function test_un_administrador_puede_registrar_un_medico_con_varias_especialidades(): void
    {
        $administrador = $this->crearAdministrador();
        $cardiologia = Specialty::factory()->create(['name' => 'Cardiología']);
        $medicinaGeneral = Specialty::factory()->create(['name' => 'Medicina General']);

        $response = $this->actingAs($administrador)->post(
            route('administrativo.medicos.store'),
            [
                'internal_code' => '  med-901  ',
                'first_name' => '  Laura   Sofía ',
                'last_name' => '  Gómez   Ríos ',
                'specialties' => [$cardiologia->id, $medicinaGeneral->id],
                'is_active' => '1',
            ],
        );

        $response
            ->assertRedirect(route('administrativo.medicos.index'))
            ->assertSessionHas('status', 'Médico registrado correctamente.');

        $medico = Doctor::query()->where('internal_code', 'MED-901')->firstOrFail();

        $this->assertSame('Laura Sofía', $medico->first_name);
        $this->assertSame('Gómez Ríos', $medico->last_name);
        $this->assertTrue($medico->is_active);
        $this->assertDatabaseHas('doctor_specialty', [
            'doctor_id' => $medico->id,
            'specialty_id' => $cardiologia->id,
        ]);
        $this->assertDatabaseHas('doctor_specialty', [
            'doctor_id' => $medico->id,
            'specialty_id' => $medicinaGeneral->id,
        ]);
    }

    public function test_no_se_permite_registrar_un_medico_con_codigo_duplicado(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create();
        Doctor::factory()->create(['internal_code' => 'MED-001']);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.create'))
            ->post(route('administrativo.medicos.store'), [
                'internal_code' => 'med-001',
                'first_name' => 'Carlos',
                'last_name' => 'Pérez',
                'specialties' => [$especialidad->id],
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('internal_code');
        $this->assertDatabaseCount('doctors', 1);
    }

    public function test_no_se_permite_repetir_la_misma_especialidad_en_un_medico(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create();

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.create'))
            ->post(route('administrativo.medicos.store'), [
                'internal_code' => 'MED-906',
                'first_name' => 'Carlos',
                'last_name' => 'Pérez',
                'specialties' => [$especialidad->id, $especialidad->id],
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('specialties.1');
        $this->assertDatabaseCount('doctors', 0);
        $this->assertDatabaseCount('doctor_specialty', 0);
    }

    public function test_no_se_puede_cambiar_el_codigo_de_un_medico_por_uno_existente(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create();
        Doctor::factory()->create(['internal_code' => 'MED-001']);
        $medico = Doctor::factory()->create(['internal_code' => 'MED-002']);
        $medico->specialties()->attach($especialidad);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.edit', $medico))
            ->put(route('administrativo.medicos.update', $medico), [
                'internal_code' => 'med-001',
                'first_name' => $medico->first_name,
                'last_name' => $medico->last_name,
                'specialties' => [$especialidad->id],
            ]);

        $response->assertSessionHasErrors('internal_code');
        $this->assertSame('MED-002', $medico->fresh()->internal_code);
    }

    public function test_el_registro_de_medico_exige_especialidades_existentes(): void
    {
        $administrador = $this->crearAdministrador();

        $sinEspecialidades = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.create'))
            ->post(route('administrativo.medicos.store'), [
                'internal_code' => 'MED-902',
                'first_name' => 'Carlos',
                'last_name' => 'Pérez',
                'specialties' => [],
            ]);

        $sinEspecialidades->assertSessionHasErrors('specialties');

        $especialidadInexistente = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.create'))
            ->post(route('administrativo.medicos.store'), [
                'internal_code' => 'MED-903',
                'first_name' => 'Carlos',
                'last_name' => 'Pérez',
                'specialties' => [999999],
            ]);

        $especialidadInexistente->assertSessionHasErrors('specialties.0');
        $this->assertDatabaseCount('doctors', 0);
    }

    public function test_un_administrador_puede_editar_un_medico_y_sus_especialidades(): void
    {
        $administrador = $this->crearAdministrador();
        $cardiologia = Specialty::factory()->create(['name' => 'Cardiología']);
        $dermatologia = Specialty::factory()->create(['name' => 'Dermatología']);
        $medico = Doctor::factory()->create([
            'internal_code' => 'MED-904',
            'first_name' => 'Andrés',
            'last_name' => 'Ramírez',
        ]);
        $medico->specialties()->attach([$cardiologia->id, $dermatologia->id]);

        $formulario = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.edit', $medico));

        $formulario
            ->assertOk()
            ->assertSee('Editar médico')
            ->assertSee('MED-904')
            ->assertSee('Cardiología')
            ->assertSee('name="_method" value="PUT"', false);

        $response = $this->actingAs($administrador)->put(
            route('administrativo.medicos.update', $medico),
            [
                'internal_code' => 'MED-905',
                'first_name' => 'Andrés Felipe',
                'last_name' => 'Ramírez Soto',
                'specialties' => [$dermatologia->id],
            ],
        );

        $response
            ->assertRedirect(route('administrativo.medicos.index'))
            ->assertSessionHas('status', 'Médico actualizado correctamente.');

        $medico->refresh();

        $this->assertSame('MED-905', $medico->internal_code);
        $this->assertSame('Andrés Felipe', $medico->first_name);
        $this->assertSame(['Dermatología'], $medico->specialties()->pluck('name')->all());
    }

    public function test_no_se_puede_quitar_una_especialidad_asociada_a_citas_futuras(): void
    {
        $administrador = $this->crearAdministrador();
        $cardiologia = Specialty::factory()->create(['name' => 'Cardiología']);
        $dermatologia = Specialty::factory()->create(['name' => 'Dermatología']);
        $medico = Doctor::factory()->create();
        $medico->specialties()->attach([$cardiologia->id, $dermatologia->id]);
        $paciente = Patient::factory()->create();

        $cita = $this->crearCita($paciente, $medico, $cardiologia);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.edit', $medico))
            ->put(route('administrativo.medicos.update', $medico), [
                'internal_code' => $medico->internal_code,
                'first_name' => $medico->first_name,
                'last_name' => $medico->last_name,
                'specialties' => [$dermatologia->id],
            ]);

        $response->assertSessionHasErrors('specialties');

        $this->assertTrue($medico->fresh()->specialties()->whereKey($cardiologia->id)->exists());
        $this->assertDatabaseHas('appointments', [
            'id' => $cita->id,
            'doctor_id' => $medico->id,
            'specialty_id' => $cardiologia->id,
            'status' => AppointmentStatus::SCHEDULED->value,
        ]);
    }

    public function test_un_administrador_puede_desactivar_y_reactivar_un_medico_conservando_citas(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create();
        $medico = Doctor::factory()->create();
        $paciente = Patient::factory()->create();
        $cita = $this->crearCita($paciente, $medico, $especialidad);

        $desactivar = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.index'))
            ->patch(route('administrativo.medicos.estado', $medico));

        $desactivar
            ->assertRedirect(route('administrativo.medicos.index'))
            ->assertSessionHas('status', 'Médico desactivado correctamente.')
            ->assertSessionHas('warning');

        $this->assertStringContainsString('1 cita futura', (string) session('warning'));
        $this->assertFalse($medico->fresh()->is_active);
        $this->assertDatabaseHas('appointments', ['id' => $cita->id]);

        $reactivar = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.index'))
            ->patch(route('administrativo.medicos.estado', $medico));

        $reactivar
            ->assertRedirect(route('administrativo.medicos.index'))
            ->assertSessionHas('status', 'Médico reactivado correctamente.');

        $this->assertTrue($medico->fresh()->is_active);
    }

    public function test_la_busqueda_los_filtros_y_la_paginacion_de_medicos_funcionan(): void
    {
        $administrador = $this->crearAdministrador();
        $cardiologia = Specialty::factory()->create(['name' => 'Cardiología']);
        $dermatologia = Specialty::factory()->create(['name' => 'Dermatología']);

        $buscado = Doctor::factory()->create([
            'internal_code' => 'MED-BUSCAR',
            'first_name' => 'Ana',
            'last_name' => 'Torres',
        ]);
        $buscado->specialties()->attach($cardiologia);

        $inactivo = Doctor::factory()->inactive()->create([
            'internal_code' => 'MED-INACTIVO',
            'first_name' => 'Bruno',
            'last_name' => 'Salas',
        ]);
        $inactivo->specialties()->attach($dermatologia);

        foreach (range(1, 9) as $numero) {
            Doctor::factory()->create([
                'internal_code' => 'MED-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
            ]);
        }

        $porCodigo = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.index', ['buscar' => 'MED-BUSCAR']));

        $porCodigo
            ->assertOk()
            ->assertViewHas('medicos', fn ($medicos): bool => $medicos->total() === 1
                && $medicos->first()->is($buscado));

        $porNombreCompleto = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.index', ['buscar' => 'Ana Torres']));

        $porNombreCompleto
            ->assertOk()
            ->assertViewHas('medicos', fn ($medicos): bool => $medicos->total() === 1
                && $medicos->first()->is($buscado));

        $porEstado = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.index', ['estado' => 'inactivos']));

        $porEstado
            ->assertOk()
            ->assertViewHas('medicos', fn ($medicos): bool => $medicos->total() === 1
                && $medicos->first()->is($inactivo));

        $porEspecialidad = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.index', ['especialidad' => $cardiologia->id]));

        $porEspecialidad
            ->assertOk()
            ->assertViewHas('medicos', fn ($medicos): bool => $medicos->total() === 1
                && $medicos->first()->is($buscado));

        $primeraPagina = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.index'));

        $primeraPagina
            ->assertOk()
            ->assertViewHas('medicos', fn ($medicos): bool => $medicos->total() === 11
                && $medicos->count() === 10
                && $medicos->currentPage() === 1)
            ->assertSee('Mostrando 1–10 de 11')
            ->assertSee('Siguiente');

        $segundaPagina = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.index', ['page' => 2]));

        $segundaPagina
            ->assertOk()
            ->assertViewHas('medicos', fn ($medicos): bool => $medicos->count() === 1);
    }

    public function test_los_campos_obligatorios_del_medico_son_validados(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.medicos.create'))
            ->post(route('administrativo.medicos.store'), []);

        $response->assertSessionHasErrors([
            'internal_code',
            'first_name',
            'last_name',
            'specialties',
        ]);

        $this->assertDatabaseCount('doctors', 0);
    }

    private function crearAdministrador(): User
    {
        return User::factory()->create([
            'name' => 'Administrador de Prueba',
            'email' => config('citavital.admin_email'),
        ]);
    }

    private function crearCita(Patient $paciente, Doctor $medico, Specialty $especialidad): Appointment
    {
        $inicio = now()->addDay()->setTime(9, 0);

        return Appointment::query()->create([
            'reference' => 'CV-MED-001',
            'patient_id' => $paciente->id,
            'doctor_id' => $medico->id,
            'specialty_id' => $especialidad->id,
            'slot_id' => null,
            'scheduled_at' => $inicio,
            'ends_at' => $inicio->copy()->addMinutes(30),
            'status' => AppointmentStatus::SCHEDULED,
        ]);
    }
}
