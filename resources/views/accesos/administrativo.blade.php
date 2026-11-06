@extends('layouts.publico')

@section('titulo', 'Acceso administrativo | CitaVital')
@section('descripcion', 'Acceso exclusivo para el personal administrativo autorizado de CitaVital.')

@push('estilos')
    @vite('resources/css/acceso.css')
@endpush

@push('scripts')
    @vite('resources/js/acceso.js')
@endpush

@section('contenido')
    <section class="acceso" aria-labelledby="titulo-acceso">
        <div class="contenedor acceso__contenedor">
            <div class="acceso__tarjeta">
                <aside class="acceso__lateral acceso__lateral--administrativo">
                    <a class="acceso__marca" href="{{ route('inicio') }}" aria-label="CitaVital, ir a la página de inicio">
                        <x-logo-citavital :tamano="46" />
                        <span class="marca__texto">Cita<span>Vital</span></span>
                    </a>

                    <div class="acceso__lateral-contenido">
                        <span class="etiqueta etiqueta--claro">
                            <x-icono nombre="admin" :tamano="15" />
                            Personal autorizado
                        </span>
                        <h2>Gestión clara, atención organizada</h2>
                        <p>
                            Administra pacientes, médicos, disponibilidad y citas desde un panel
                            centralizado, con información visible y acciones controladas.
                        </p>

                        <ul class="acceso__beneficios">
                            <li>
                                <span class="acceso__beneficio-icono"><x-icono nombre="usuarios" :tamano="18" /></span>
                                <span>Pacientes y médicos en un solo panel</span>
                            </li>
                            <li>
                                <span class="acceso__beneficio-icono"><x-icono nombre="agenda" :tamano="18" /></span>
                                <span>Agenda y disponibilidad organizadas</span>
                            </li>
                            <li>
                                <span class="acceso__beneficio-icono"><x-icono nombre="telefono" :tamano="18" /></span>
                                <span>Seguimiento de inasistencias</span>
                            </li>
                        </ul>
                    </div>

                    <p class="acceso__lateral-nota">
                        Los pacientes no tienen acceso a este módulo. Cada sesión se validará con los
                        controles de seguridad correspondientes.
                    </p>
                </aside>

                <div class="acceso__formulario">
                    <a class="volver" href="{{ route('inicio') }}">
                        <x-icono nombre="flecha-izquierda" :tamano="17" />
                        Volver al inicio
                    </a>

                    <div class="acceso__encabezado">
                        <p class="sobre-titulo">Acceso administrativo</p>
                        <h1 id="titulo-acceso">Inicia sesión en tu panel</h1>
                        <p>Ingresa con las credenciales asignadas a tu cuenta administrativa.</p>
                    </div>

                    <form class="formulario" data-formulario-acceso="administrativo" method="post" novalidate>
                        @csrf

                        <div class="campo" data-campo="correo">
                            <label class="campo__etiqueta" for="correo">Correo electrónico</label>
                            <div class="campo__control">
                                <span class="campo__icono">
                                    <x-icono nombre="correo" :tamano="20" />
                                </span>
                                <input
                                    class="campo__entrada"
                                    id="correo"
                                    name="email"
                                    type="email"
                                    autocomplete="username"
                                    placeholder="nombre@institucion.demo"
                                    aria-describedby="correo-error"
                                    required
                                    data-correo-admin
                                >
                            </div>
                            <p class="campo__error" id="correo-error" role="alert" data-error-correo hidden></p>
                        </div>

                        <div class="campo" data-campo="password">
                            <label class="campo__etiqueta" for="password">Contraseña</label>
                            <div class="campo__control campo__control--accion">
                                <span class="campo__icono">
                                    <x-icono nombre="candado" :tamano="20" />
                                </span>
                                <input
                                    class="campo__entrada"
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    placeholder="Ingresa tu contraseña"
                                    aria-describedby="password-error"
                                    required
                                    data-password
                                >
                                <button
                                    class="campo__boton"
                                    type="button"
                                    aria-controls="password"
                                    aria-pressed="false"
                                    aria-label="Mostrar contraseña"
                                    data-alternar-password
                                >
                                    <x-icono nombre="ojo" :tamano="20" class="icono-ojo" />
                                    <x-icono nombre="ojo-cerrado" :tamano="20" class="icono-ojo-cerrado" />
                                </button>
                            </div>
                            <p class="campo__error" id="password-error" role="alert" data-error-password hidden></p>
                        </div>

                        <div class="aviso aviso--info">
                            <span class="aviso__icono"><x-icono nombre="candado" :tamano="20" /></span>
                            <p>
                                <strong>Acceso restringido.</strong> Las credenciales de demostración se
                                configuran fuera del código fuente y no se muestran en esta pantalla.
                                La autenticación se habilitará en una fase posterior.
                            </p>
                        </div>

                        <button class="boton boton--primario boton--grande boton--bloque" type="submit">
                            Iniciar sesión
                            <x-icono nombre="flecha-derecha" :tamano="18" />
                        </button>

                        <div class="aviso aviso--info" role="status" aria-live="polite" data-estado-acceso hidden>
                            <span class="aviso__icono"><x-icono nombre="alerta" :tamano="20" /></span>
                            <p></p>
                        </div>
                    </form>

                    <p class="acceso__alterno">
                        ¿Buscas el portal del paciente?
                        <a href="{{ route('paciente.login') }}">Acceso de pacientes</a>
                    </p>
                </div>
            </div>

            <p class="acceso__legal">
                <x-icono nombre="candado" :tamano="15" />
                Prototipo académico. No introduzcas credenciales reales en esta demostración.
            </p>
        </div>
    </section>
@endsection
