<?php

namespace Tests\Feature\Administrativo;

use App\Models\Availability;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeguridadModulosAdministrativosTest extends TestCase
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

    public function test_los_visitantes_no_pueden_acceder_a_especialidades_medicos_ni_horarios(): void
    {
        $rutas = [
            route('administrativo.especialidades.index'),
            route('administrativo.especialidades.create'),
            route('administrativo.medicos.index'),
            route('administrativo.medicos.create'),
            route('administrativo.horarios.index'),
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertRedirect(route('administrativo.login'));
        }
    }

    public function test_los_visitantes_no_pueden_ejecutar_operaciones_de_escritura(): void
    {
        $operaciones = [
            ['post', route('administrativo.especialidades.store')],
            ['post', route('administrativo.medicos.store')],
            ['post', route('administrativo.horarios.disponibilidades.store')],
            ['post', route('administrativo.horarios.bloqueos.store')],
            ['post', route('administrativo.horarios.generar')],
        ];

        foreach ($operaciones as [$metodo, $ruta]) {
            $this->{$metodo}($ruta)->assertRedirect(route('administrativo.login'));
        }

        $this->assertDatabaseCount('specialties', 0);
        $this->assertDatabaseCount('doctors', 0);
        $this->assertDatabaseCount('availabilities', 0);
        $this->assertDatabaseCount('availability_blocks', 0);
    }

    public function test_un_usuario_no_administrador_recibe_403_en_los_tres_modulos(): void
    {
        $usuario = User::factory()->create([
            'email' => 'usuario.no.admin@uniajs.edu.co',
        ]);

        $rutas = [
            route('administrativo.especialidades.index'),
            route('administrativo.medicos.index'),
            route('administrativo.horarios.index'),
        ];

        foreach ($rutas as $ruta) {
            $this->actingAs($usuario)->get($ruta)->assertForbidden();
        }

        $this->actingAs($usuario)
            ->post(route('administrativo.especialidades.store'), ['name' => 'Cardiología'])
            ->assertForbidden();

        $this->actingAs($usuario)
            ->post(route('administrativo.medicos.store'), [])
            ->assertForbidden();

        $this->actingAs($usuario)
            ->post(route('administrativo.horarios.disponibilidades.store'), [])
            ->assertForbidden();

        $this->actingAs($usuario)
            ->post(route('administrativo.horarios.bloqueos.store'), [])
            ->assertForbidden();

        $this->actingAs($usuario)
            ->post(route('administrativo.horarios.generar'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('specialties', 0);
        $this->assertDatabaseCount('doctors', 0);
        $this->assertDatabaseCount('availabilities', 0);
        $this->assertDatabaseCount('availability_blocks', 0);
    }

    public function test_un_usuario_no_administrador_no_puede_modificar_ni_eliminar_registros(): void
    {
        $usuario = User::factory()->create([
            'email' => 'usuario.escritura@uniajs.edu.co',
        ]);
        $especialidad = Specialty::factory()->create();
        $medico = Doctor::factory()->create();
        $disponibilidad = Availability::factory()->create(['doctor_id' => $medico->id]);
        $bloqueo = AvailabilityBlock::query()->create([
            'doctor_id' => $medico->id,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(10, 0),
            'reason' => 'Prueba',
            'is_active' => true,
        ]);

        $this->actingAs($usuario)
            ->patch(route('administrativo.especialidades.estado', $especialidad))
            ->assertForbidden();

        $this->actingAs($usuario)
            ->put(route('administrativo.medicos.update', $medico), [])
            ->assertForbidden();

        $this->actingAs($usuario)
            ->patch(route('administrativo.horarios.disponibilidades.estado', $disponibilidad))
            ->assertForbidden();

        $this->actingAs($usuario)
            ->delete(route('administrativo.horarios.disponibilidades.destroy', $disponibilidad))
            ->assertForbidden();

        $this->actingAs($usuario)
            ->patch(route('administrativo.horarios.bloqueos.estado', $bloqueo))
            ->assertForbidden();

        $this->actingAs($usuario)
            ->delete(route('administrativo.horarios.bloqueos.destroy', $bloqueo))
            ->assertForbidden();
    }

    public function test_los_formularios_administrativos_incluyen_csrf_y_acciones_seguras(): void
    {
        $administrador = User::factory()->create([
            'email' => config('citavital.admin_email'),
        ]);
        $medico = Doctor::factory()->create();
        $especialidad = Specialty::factory()->create();
        Availability::factory()->create(['doctor_id' => $medico->id]);

        $formularioEspecialidad = $this->actingAs($administrador)
            ->get(route('administrativo.especialidades.create'));

        $formularioEspecialidad
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('administrativo.especialidades.store').'"', false);

        $formularioMedico = $this->actingAs($administrador)
            ->get(route('administrativo.medicos.create'));

        $formularioMedico
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('administrativo.medicos.store').'"', false)
            ->assertSee('name="specialties[]"', false);

        $horarios = $this->actingAs($administrador)
            ->get(route('administrativo.horarios.index', ['doctor' => $medico->id]));

        $horarios
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('administrativo.horarios.disponibilidades.store').'"', false)
            ->assertSee('action="'.route('administrativo.horarios.bloqueos.store').'"', false)
            ->assertSee('action="'.route('administrativo.horarios.generar').'"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_method" value="DELETE"', false);
    }
}
