@extends('layouts.publico')

@section('titulo', 'Acceso de pacientes | CitaVital')
@section('descripcion', 'Accede al portal del paciente de CitaVital con tu número de cédula para consultar y solicitar tus citas médicas.')

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
                <aside class="acceso__lateral acceso__lateral--paciente">
                    <a class="acceso__marca" href="{{ route('inicio') }}" aria-label="CitaVital, ir a la página de inicio">
                        <x-logo-citavital :tamano="46" />
                        <span class="marca__texto">Cita<span>Vital</span></span>
                    </a>

                    <div class="acceso__lateral-contenido">
                        <span class="etiqueta etiqueta--claro">
                            <x-icono nombre="corazon" :tamano="15" />
                            Portal del paciente
                        </span>
                        <h2>Tu salud, en un solo lugar</h2>
                        <p>
                            Consulta tus próximas citas, revisa tu historial y solicita nuevos
                            espacios de atención desde un entorno claro y seguro.
                        </p>

                        <ul class="acceso__beneficios">
                            <li>
                                <span class="acceso__beneficio-icono"><x-icono nombre="calendario" :tamano="18" /></span>
                                <span>Consulta tus próximas citas</span>
                            </li>
                            <li>
                                <span class="acceso__beneficio-icono"><x-icono nombre="historial" :tamano="18" /></span>
                                <span>Revisa tu historial de atención</span>
                            </li>
                            <li>
                                <span class="acceso__beneficio-icono"><x-icono nombre="reloj" :tamano="18" /></span>
                                <span>Solicita nuevos horarios disponibles</span>
                            </li>
                        </ul>
                    </div>

                    <p class="acceso__lateral-nota">
                        Acceso exclusivo para pacientes registrados previamente por el área administrativa.
                    </p>
                </aside>

                <div class="acceso__formulario">
                    <a class="volver" href="{{ route('inicio') }}">
                        <x-icono nombre="flecha-izquierda" :tamano="17" />
                        Volver al inicio
                    </a>

                    <div class="acceso__encabezado">
                        <p class="sobre-titulo">Acceso de pacientes</p>
                        <h1 id="titulo-acceso">Ingresa con tu cédula</h1>
                        <p>Usa el número de documento con el que fuiste registrado en CitaVital.</p>
                    </div>

                    <form class="formulario" data-formulario-acceso="paciente" method="post" novalidate>
                        @csrf

                        <div class="campo" data-campo="cedula">
                            <label class="campo__etiqueta" for="cedula">Número de cédula</label>
                            <div class="campo__control">
                                <span class="campo__icono">
                                    <x-icono nombre="documento" :tamano="20" />
                                </span>
                                <input
                                    class="campo__entrada"
                                    id="cedula"
                                    name="document_number"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    pattern="[0-9]{8}|[0-9]{10}"
                                    maxlength="10"
                                    placeholder="Ej. 1023456789"
                                    aria-describedby="cedula-ayuda cedula-error"
                                    required
                                    data-cedula
                                >
                            </div>
                            <p class="campo__ayuda" id="cedula-ayuda">
                                Solo números, sin puntos ni espacios. Debe tener 8 o 10 dígitos.
                            </p>
                            <p class="campo__error" id="cedula-error" role="alert" data-error-cedula hidden></p>
                        </div>

                        <div class="aviso aviso--info">
                            <span class="aviso__icono"><x-icono nombre="escudo" :tamano="20" /></span>
                            <p>
                                <strong>Modo demostración.</strong> Este acceso solo se habilitará en
                                entorno local con pacientes ficticios. En un entorno real se exigirá una
                                segunda verificación de identidad.
                            </p>
                        </div>

                        <button class="boton boton--primario boton--grande boton--bloque" type="submit">
                            Continuar
                            <x-icono nombre="flecha-derecha" :tamano="18" />
                        </button>

                        <div class="aviso aviso--info" role="status" aria-live="polite" data-estado-acceso hidden>
                            <span class="aviso__icono"><x-icono nombre="alerta" :tamano="20" /></span>
                            <p></p>
                        </div>
                    </form>

                    <p class="acceso__alterno">
                        ¿Haces parte del personal administrativo?
                        <a href="{{ route('administrativo.login') }}">Acceso administrativo</a>
                    </p>
                </div>
            </div>

            <p class="acceso__legal">
                <x-icono nombre="candado" :tamano="15" />
                Prototipo académico. No uses información real de pacientes en esta demostración.
            </p>
        </div>
    </section>
@endsection
