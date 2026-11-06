<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendPublicoTest extends TestCase
{
    public function test_la_pagina_principal_carga_correctamente(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('CitaVital')
            ->assertSee('Tu plataforma de gestión de citas médicas')
            ->assertSee('Solicita, consulta y realiza seguimiento a tus citas de manera rápida y organizada.')
            ->assertSee('¿Quiénes somos?')
            ->assertSee('Citas')
            ->assertSee('Administrativo')
            ->assertSee('href="'.route('quienes-somos').'"', false)
            ->assertSee('href="'.route('paciente.login').'"', false)
            ->assertSee('href="'.route('administrativo.login').'"', false);
    }

    public function test_la_pagina_quienes_somos_carga_y_presenta_la_informacion_institucional(): void
    {
        $response = $this->get(route('quienes-somos'));

        $response
            ->assertOk()
            ->assertSee('Quiénes somos')
            ->assertSee('Misión')
            ->assertSee('Visión')
            ->assertSee('Responsabilidad')
            ->assertSee('Protección de la información')
            ->assertSee('Nuestros servicios')
            ->assertSee('Preguntas frecuentes')
            ->assertSee('CitaVital es un prototipo académico');
    }

    public function test_el_acceso_de_pacientes_presenta_el_formulario_de_cedula(): void
    {
        $response = $this->get(route('paciente.login'));

        $response
            ->assertOk()
            ->assertSee('Portal del paciente')
            ->assertSee('Número de cédula')
            ->assertSee('8 o 10 dígitos')
            ->assertSee('data-cedula', false)
            ->assertSee('Modo demostración');
    }

    public function test_el_acceso_administrativo_presenta_sus_campos_sin_exponer_credenciales(): void
    {
        $response = $this->get(route('administrativo.login'));

        $response
            ->assertOk()
            ->assertSee('Ingresa tus credenciales para continuar.')
            ->assertSee('Correo electrónico')
            ->assertSee('Contraseña')
            ->assertDontSee('administrativo.demo@uniajs.edu.co')
            ->assertDontSee('Contraseña de demostración');
    }

    public function test_la_politica_de_privacidad_demostrativa_carga_correctamente(): void
    {
        $response = $this->get(route('politica-privacidad'));

        $response
            ->assertOk()
            ->assertSee('Política de privacidad demostrativa')
            ->assertSee('Aviso académico')
            ->assertSee('contacto@citavital.demo');
    }
}
