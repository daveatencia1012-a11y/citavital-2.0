<?php

namespace Tests\Feature\Administrativo;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutenticacionAdministrativaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_formulario_administrativo_carga_con_csrf_y_accion_segura(): void
    {
        $response = $this->get(route('administrativo.login'));

        $response
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.route('administrativo.login.store').'"', false)
            ->assertSee('Ingresa tus credenciales para continuar.')
            ->assertDontSee(config('citavital.admin_email'));
    }

    public function test_un_administrador_puede_iniciar_sesion_y_la_sesion_se_regenera(): void
    {
        $password = $this->crearAdministrador();
        $administrador = $this->administrador();

        $this->startSession();
        $sesionAnterior = $this->app['session']->getId();

        $response = $this->post(route('administrativo.login.store'), [
            'email' => $administrador->email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('administrativo.dashboard'));

        $this->assertAuthenticatedAs($administrador);
        $this->assertNotSame($sesionAnterior, $this->app['session']->getId());
    }

    public function test_una_contrasena_incorrecta_no_autentica_al_usuario(): void
    {
        $this->crearAdministrador();

        $response = $this
            ->from(route('administrativo.login'))
            ->post(route('administrativo.login.store'), [
                'email' => config('citavital.admin_email'),
                'password' => Str::random(24),
            ]);

        $response
            ->assertRedirect(route('administrativo.login'))
            ->assertSessionHasErrors(['email' => 'Las credenciales ingresadas no son correctas.']);

        $this->assertGuest();
    }

    public function test_un_correo_inexistente_no_autentica_al_usuario(): void
    {
        $response = $this
            ->from(route('administrativo.login'))
            ->post(route('administrativo.login.store'), [
                'email' => 'noexiste@uniajs.edu.co',
                'password' => Str::random(24),
            ]);

        $response
            ->assertRedirect(route('administrativo.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_los_campos_obligatorios_son_validados(): void
    {
        $response = $this
            ->from(route('administrativo.login'))
            ->post(route('administrativo.login.store'), []);

        $response
            ->assertRedirect(route('administrativo.login'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_el_dashboard_administrativo_requiere_autenticacion(): void
    {
        $this->get(route('administrativo.dashboard'))
            ->assertRedirect(route('administrativo.login'));
    }

    public function test_un_usuario_no_administrador_no_puede_acceder_al_dashboard(): void
    {
        $paciente = User::factory()->create([
            'email' => 'paciente@uniajs.edu.co',
        ]);

        $this->actingAs($paciente)
            ->get(route('administrativo.dashboard'))
            ->assertForbidden();
    }

    public function test_un_usuario_no_administrador_no_puede_cerrar_la_sesion_administrativa(): void
    {
        $paciente = User::factory()->create([
            'email' => 'paciente.logout@uniajs.edu.co',
        ]);

        $this->actingAs($paciente)
            ->post(route('administrativo.logout'))
            ->assertForbidden();
    }

    public function test_un_administrador_autenticado_puede_ver_el_dashboard(): void
    {
        $this->crearAdministrador();
        $administrador = $this->administrador();

        $response = $this->actingAs($administrador)->get(route('administrativo.dashboard'));

        $response
            ->assertOk()
            ->assertSee($administrador->name)
            ->assertSee($administrador->email)
            ->assertSee('Sesión activa')
            ->assertSee('Cerrar sesión')
            ->assertSee('name="_token"', false);
    }

    public function test_un_administrador_autenticado_es_redirigido_desde_el_login_al_dashboard(): void
    {
        $this->crearAdministrador();

        $this->actingAs($this->administrador())
            ->get(route('administrativo.login'))
            ->assertRedirect(route('administrativo.dashboard'));
    }

    public function test_el_cierre_de_sesion_invalida_la_sesion(): void
    {
        $this->crearAdministrador();
        $administrador = $this->administrador();

        $response = $this->actingAs($administrador)->post(route('administrativo.logout'));

        $response
            ->assertRedirect(route('administrativo.login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_los_intentos_de_inicio_de_sesion_estan_limitados(): void
    {
        $password = $this->crearAdministrador();
        $email = config('citavital.admin_email');

        for ($intento = 0; $intento < 5; $intento++) {
            $this->post(route('administrativo.login.store'), [
                'email' => $email,
                'password' => 'incorrecta-'.$intento,
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('administrativo.login.store'), [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Demasiados intentos',
            $response->getSession()->get('errors')->first('email')
        );
        $this->assertGuest();
    }

    private function crearAdministrador(): string
    {
        $password = Str::random(24);

        config(['citavital.admin_password' => $password]);

        $this->seed(AdminUserSeeder::class);

        return $password;
    }

    private function administrador(): User
    {
        return User::query()
            ->where('email', config('citavital.admin_email'))
            ->firstOrFail();
    }
}
