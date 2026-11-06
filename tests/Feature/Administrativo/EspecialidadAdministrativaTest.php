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

class EspecialidadAdministrativaTest extends TestCase
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

    public function test_un_administrador_puede_ver_el_listado_de_especialidades(): void
    {
        $administrador = $this->crearAdministrador();
        $cardiologia = Specialty::factory()->create(['name' => 'Cardiología']);
        $inactiva = Specialty::factory()->create([
            'name' => 'Dermatología',
            'is_active' => false,
        ]);

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.especialidades.index'));

        $response
            ->assertOk()
            ->assertSee('Listado de especialidades')
            ->assertSee('ESP-'.str_pad((string) $cardiologia->id, 3, '0', STR_PAD_LEFT))
            ->assertSee('Cardiología')
            ->assertSee('Dermatología')
            ->assertSee('Activo')
            ->assertSee('Inactivo')
            ->assertSee('Nueva especialidad');
    }

    public function test_el_formulario_de_registro_carga_con_csrf(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)
            ->get(route('administrativo.especialidades.create'));

        $response
            ->assertOk()
            ->assertSee('Nueva especialidad')
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('administrativo.especialidades.store').'"', false)
            ->assertSee('Nombre de la especialidad')
            ->assertSee('Descripción')
            ->assertSee('Especialidad activa');
    }

    public function test_un_administrador_puede_registrar_una_especialidad(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)->post(
            route('administrativo.especialidades.store'),
            [
                'name' => '  Cardiología   Clínica  ',
                'description' => '  Atención cardiovascular.  ',
                'is_active' => '1',
            ],
        );

        $response
            ->assertRedirect(route('administrativo.especialidades.index'))
            ->assertSessionHas('status', 'Especialidad registrada correctamente.');

        $this->assertDatabaseHas('specialties', [
            'name' => 'Cardiología Clínica',
            'description' => 'Atención cardiovascular.',
            'is_active' => true,
        ]);
    }

    public function test_no_se_permite_registrar_una_especialidad_duplicada_ignorando_mayusculas_y_espacios(): void
    {
        $administrador = $this->crearAdministrador();
        Specialty::factory()->create(['name' => 'Cardiología Clínica']);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.especialidades.create'))
            ->post(route('administrativo.especialidades.store'), [
                'name' => '  cardiología   CLÍNICA  ',
                'description' => null,
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('specialties', 1);
    }

    public function test_un_administrador_puede_editar_una_especialidad(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create([
            'name' => 'Medicina General',
            'description' => 'Consulta general.',
        ]);

        $formulario = $this->actingAs($administrador)
            ->get(route('administrativo.especialidades.edit', $especialidad));

        $formulario
            ->assertOk()
            ->assertSee('Editar especialidad')
            ->assertSee('Medicina General')
            ->assertSee('name="_method" value="PUT"', false);

        $response = $this->actingAs($administrador)->put(
            route('administrativo.especialidades.update', $especialidad),
            [
                'name' => 'Medicina Familiar',
                'description' => 'Atención integral.',
            ],
        );

        $response
            ->assertRedirect(route('administrativo.especialidades.index'))
            ->assertSessionHas('status', 'Especialidad actualizada correctamente.');

        $especialidad->refresh();

        $this->assertSame('Medicina Familiar', $especialidad->name);
        $this->assertSame('Atención integral.', $especialidad->description);
    }

    public function test_no_se_puede_editar_una_especialidad_con_el_nombre_de_otra(): void
    {
        $administrador = $this->crearAdministrador();
        Specialty::factory()->create(['name' => 'Cardiología']);
        $especialidad = Specialty::factory()->create(['name' => 'Dermatología']);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.especialidades.edit', $especialidad))
            ->put(route('administrativo.especialidades.update', $especialidad), [
                'name' => '  CARDIOLOGÍA  ',
                'description' => null,
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame('Dermatología', $especialidad->fresh()->name);
    }

    public function test_un_administrador_puede_desactivar_y_reactivar_una_especialidad(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create(['is_active' => true]);

        $this->actingAs($administrador)
            ->from(route('administrativo.especialidades.index'))
            ->patch(route('administrativo.especialidades.estado', $especialidad))
            ->assertRedirect(route('administrativo.especialidades.index'))
            ->assertSessionHas('status', 'Especialidad desactivada correctamente.');

        $this->assertFalse($especialidad->fresh()->is_active);

        $this->actingAs($administrador)
            ->from(route('administrativo.especialidades.index'))
            ->patch(route('administrativo.especialidades.estado', $especialidad))
            ->assertRedirect(route('administrativo.especialidades.index'))
            ->assertSessionHas('status', 'Especialidad reactivada correctamente.');

        $this->assertTrue($especialidad->fresh()->is_active);
    }

    public function test_la_desactivacion_con_citas_futuras_muestra_advertencia_y_conserva_las_citas(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Specialty::factory()->create();
        $medico = Doctor::factory()->create();
        $paciente = Patient::factory()->create();

        $cita = $this->crearCita($paciente, $medico, $especialidad);

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.especialidades.index'))
            ->patch(route('administrativo.especialidades.estado', $especialidad));

        $response
            ->assertRedirect(route('administrativo.especialidades.index'))
            ->assertSessionHas('status', 'Especialidad desactivada correctamente.')
            ->assertSessionHas('warning');

        $this->assertStringContainsString('1 cita futura', (string) session('warning'));
        $this->assertFalse($especialidad->fresh()->is_active);
        $this->assertDatabaseHas('appointments', [
            'id' => $cita->id,
            'specialty_id' => $especialidad->id,
            'status' => AppointmentStatus::SCHEDULED->value,
        ]);
    }

    public function test_la_busqueda_los_filtros_y_la_paginacion_de_especialidades_funcionan(): void
    {
        $administrador = $this->crearAdministrador();

        $cardiologia = Specialty::factory()->create(['name' => 'Cardiología']);
        Specialty::factory()->create(['name' => 'Dermatología', 'is_active' => false]);

        foreach (range(1, 10) as $numero) {
            Specialty::factory()->create([
                'name' => 'Especialidad '.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $busqueda = $this->actingAs($administrador)
            ->get(route('administrativo.especialidades.index', ['buscar' => 'Cardio']));

        $busqueda
            ->assertOk()
            ->assertViewHas('especialidades', fn ($especialidades): bool => $especialidades->total() === 1
                && $especialidades->first()->is($cardiologia));

        $inactivas = $this->actingAs($administrador)
            ->get(route('administrativo.especialidades.index', ['estado' => 'inactivos']));

        $inactivas
            ->assertOk()
            ->assertViewHas('especialidades', fn ($especialidades): bool => $especialidades->total() === 1
                && $especialidades->first()->name === 'Dermatología');

        $primeraPagina = $this->actingAs($administrador)
            ->get(route('administrativo.especialidades.index'));

        $primeraPagina
            ->assertOk()
            ->assertViewHas('especialidades', fn ($especialidades): bool => $especialidades->total() === 12
                && $especialidades->count() === 10
                && $especialidades->currentPage() === 1)
            ->assertSee('Mostrando 1–10 de 12')
            ->assertSee('Siguiente');

        $segundaPagina = $this->actingAs($administrador)
            ->get(route('administrativo.especialidades.index', ['page' => 2]));

        $segundaPagina
            ->assertOk()
            ->assertViewHas('especialidades', fn ($especialidades): bool => $especialidades->count() === 2);
    }

    public function test_los_campos_obligatorios_de_la_especialidad_son_validados(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this->actingAs($administrador)
            ->from(route('administrativo.especialidades.create'))
            ->post(route('administrativo.especialidades.store'), []);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('specialties', 0);
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
            'reference' => 'CV-ESP-001',
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
