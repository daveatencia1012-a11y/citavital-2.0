@extends('layouts.paciente')

@section('titulo', 'Resumen')
@section('descripcion', 'Resumen personal de citas médicas del paciente en CitaVital.')

@section('contenido')
    <div class="paciente-contenido">
        <section class="paciente-heroe" aria-labelledby="titulo-bienvenida">
            <div class="paciente-heroe__texto">
                <p class="paciente-sobre">Portal del paciente</p>
                <h1 id="titulo-bienvenida">Bienvenido, {{ $paciente->full_name }}</h1>
                <p>Consulta tus próximas citas, revisa tu historial y solicita una nueva cita con los especialistas de CitaVital.</p>
                <div class="paciente-heroe__acciones">
                    <a class="boton boton--primario" href="{{ route('paciente.citas.solicitar') }}">
                        <x-icono nombre="calendario" :tamano="18" />
                        Solicitar cita
                    </a>
                    <a class="boton boton--secundario" href="{{ route('paciente.citas.index') }}">
                        Ver próximas citas
                    </a>
                </div>
            </div>
            <span class="paciente-heroe__sello">
                <x-icono nombre="escudo" :tamano="17" />
                Sesión personal protegida
            </span>
        </section>

        <div class="paciente-indicadores" aria-label="Resumen de citas">
            <article class="paciente-indicador paciente-indicador--turquesa">
                <span class="paciente-indicador__icono"><x-icono nombre="calendario" :tamano="21" /></span>
                <div>
                    <p>Próximas citas</p>
                    <strong>{{ number_format($proximasCount, 0, ',', '.') }}</strong>
                    <small>Programadas a futuro</small>
                </div>
            </article>
            <article class="paciente-indicador paciente-indicador--azul">
                <span class="paciente-indicador__icono"><x-icono nombre="reloj" :tamano="21" /></span>
                <div>
                    <p>Citas pendientes</p>
                    <strong>{{ number_format($pendientesCount, 0, ',', '.') }}</strong>
                    <small>En estado programada</small>
                </div>
            </article>
            <article class="paciente-indicador paciente-indicador--petroleo">
                <span class="paciente-indicador__icono"><x-icono nombre="check-circulo" :tamano="21" /></span>
                <div>
                    <p>Citas realizadas</p>
                    <strong>{{ number_format($realizadasCount, 0, ',', '.') }}</strong>
                    <small>Atenciones completadas</small>
                </div>
            </article>
            <article class="paciente-indicador paciente-indicador--menta">
                <span class="paciente-indicador__icono"><x-icono nombre="historial" :tamano="21" /></span>
                <div>
                    <p>Historial</p>
                    <strong>{{ number_format($historialCount, 0, ',', '.') }}</strong>
                    <small>Registros conservados</small>
                </div>
            </article>
        </div>

        <div class="paciente-paneles">
            <section class="paciente-panel" aria-labelledby="titulo-proximas">
                <header class="paciente-panel__cabecera">
                    <div>
                        <p class="paciente-sobre">Agenda personal</p>
                        <h2 id="titulo-proximas">Próximas citas</h2>
                        <p>Estas son las citas programadas que tienes por atender.</p>
                    </div>
                    <a class="paciente-enlace" href="{{ route('paciente.citas.index') }}">Ver todas</a>
                </header>

                @if ($proximasCitas->isEmpty())
                    <div class="paciente-vacio">
                        <span class="paciente-vacio__icono"><x-icono nombre="calendario" :tamano="26" /></span>
                        <h3>Aún no tienes citas programadas.</h3>
                        <p>Selecciona una especialidad, un médico y un horario disponible para solicitar tu primera cita.</p>
                        <a class="boton boton--primario" href="{{ route('paciente.citas.solicitar') }}">Solicitar cita</a>
                    </div>
                @else
                    <ul class="paciente-lista-citas">
                        @foreach ($proximasCitas as $cita)
                            <li class="paciente-cita">
                                <div class="paciente-cita__fecha" aria-hidden="true">
                                    <strong>{{ $cita->scheduled_at->format('d') }}</strong>
                                    <span>{{ strtoupper($cita->scheduled_at->locale('es')->translatedFormat('M')) }}</span>
                                </div>
                                <div class="paciente-cita__cuerpo">
                                    <div class="paciente-cita__superior">
                                        <span class="paciente-referencia">{{ $cita->reference }}</span>
                                        <x-estado-cita :estado="$cita->status" />
                                    </div>
                                    <p class="paciente-cita__titulo">{{ $cita->specialty->name }}</p>
                                    <p class="paciente-cita__detalle">
                                        <x-icono nombre="estetoscopio" :tamano="16" />
                                        {{ $cita->doctor->full_name }}
                                    </p>
                                    <p class="paciente-cita__detalle">
                                        <x-icono nombre="reloj" :tamano="16" />
                                        {{ $cita->scheduled_at->locale('es')->translatedFormat('l, j \d\e F') }} · {{ $cita->scheduled_at->format('H:i') }}
                                    </p>
                                </div>
                                <a class="paciente-cita__ver" href="{{ route('paciente.citas.show', $cita) }}">Ver detalle</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <aside class="paciente-panel paciente-panel--lateral" aria-labelledby="titulo-historial-reciente">
                <header class="paciente-panel__cabecera">
                    <div>
                        <p class="paciente-sobre">Trazabilidad</p>
                        <h2 id="titulo-historial-reciente">Historial reciente</h2>
                    </div>
                    <a class="paciente-enlace" href="{{ route('paciente.citas.historial') }}">Abrir historial</a>
                </header>

                @if ($historialReciente->isEmpty())
                    <div class="paciente-vacio paciente-vacio--compacto">
                        <p>Aquí aparecerán tus citas anteriores y sus estados.</p>
                    </div>
                @else
                    <ul class="paciente-historial-lista">
                        @foreach ($historialReciente as $cita)
                            <li>
                                <div>
                                    <span class="paciente-referencia">{{ $cita->reference }}</span>
                                    <p>{{ $cita->specialty->name }}</p>
                                    <small>{{ $cita->scheduled_at->format('d/m/Y') }} · {{ $cita->doctor->full_name }}</small>
                                </div>
                                <x-estado-cita :estado="$cita->status" />
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="paciente-nota">
                    <x-icono nombre="escudo" :tamano="18" />
                    <p>Tu información es personal. CitaVital solo muestra las citas asociadas a tu sesión.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
