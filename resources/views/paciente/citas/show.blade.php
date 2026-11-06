@extends('layouts.paciente')

@section('titulo', 'Detalle de la cita')
@section('descripcion', 'Detalle de una cita médica propia en el portal del paciente de CitaVital.')

@section('contenido')
    <div class="paciente-contenido">
        <header class="paciente-pagina__cabecera">
            <div>
                <p class="paciente-sobre">Detalle de la cita</p>
                <div class="paciente-titulo-estado">
                    <h1>{{ $cita->reference }}</h1>
                    <x-estado-cita :estado="$cita->status" />
                </div>
                <p>Solicitada el {{ $cita->created_at->format('d/m/Y') }} a las {{ $cita->created_at->format('H:i') }}.</p>
            </div>
            <div class="paciente-pagina__acciones">
                <a class="boton boton--secundario" href="{{ route('paciente.citas.index') }}">
                    <x-icono nombre="flecha-izquierda" :tamano="17" />
                    Próximas citas
                </a>
                <a class="boton boton--secundario" href="{{ route('paciente.citas.historial') }}">Historial</a>
            </div>
        </header>

        <div class="paciente-detalle">
            <section class="paciente-panel" aria-labelledby="titulo-datos-cita">
                <header class="paciente-panel__cabecera">
                    <div>
                        <p class="paciente-sobre">Información de la cita</p>
                        <h2 id="titulo-datos-cita">Datos de la atención</h2>
                        <p>Esta información corresponde únicamente a tu sesión de paciente.</p>
                    </div>
                </header>

                <dl class="paciente-resumen">
                    <div>
                        <dt>Paciente</dt>
                        <dd>{{ $paciente->full_name }} · Cédula {{ $paciente->document_number }}</dd>
                    </div>
                    <div>
                        <dt>Especialidad</dt>
                        <dd>{{ $cita->specialty->name }}</dd>
                    </div>
                    <div>
                        <dt>Médico</dt>
                        <dd>{{ $cita->doctor->full_name }} · {{ $cita->doctor->internal_code }}</dd>
                    </div>
                    <div>
                        <dt>Fecha</dt>
                        <dd>{{ $cita->scheduled_at->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}</dd>
                    </div>
                    <div>
                        <dt>Hora</dt>
                        <dd>{{ $cita->scheduled_at->format('H:i') }} – {{ $cita->ends_at->format('H:i') }} (America/Bogota)</dd>
                    </div>
                    <div>
                        <dt>Estado</dt>
                        <dd><x-estado-cita :estado="$cita->status" /></dd>
                    </div>
                </dl>

                @if ($cita->cancellation_reason)
                    <div class="paciente-alerta paciente-alerta--error" role="status">
                        <x-icono nombre="alerta" :tamano="20" />
                        <div>
                            <strong>Motivo de cancelación</strong>
                            <p>{{ $cita->cancellation_reason }}</p>
                        </div>
                    </div>
                @endif
            </section>

            <aside class="paciente-panel paciente-panel--lateral" aria-labelledby="titulo-trazabilidad">
                <header class="paciente-panel__cabecera">
                    <div>
                        <p class="paciente-sobre">Trazabilidad</p>
                        <h2 id="titulo-trazabilidad">Historial del registro</h2>
                    </div>
                </header>

                <ul class="paciente-linea-tiempo">
                    <li>
                        <span class="paciente-linea-tiempo__punto" aria-hidden="true"></span>
                        <div>
                            <strong>Cita registrada</strong>
                            <p>{{ $cita->created_at->format('d/m/Y · H:i') }}</p>
                        </div>
                    </li>

                    @if ($cita->rescheduledFrom)
                        <li>
                            <span class="paciente-linea-tiempo__punto" aria-hidden="true"></span>
                            <div>
                                <strong>Reprogramada desde otra cita</strong>
                                <p>
                                    <a class="paciente-enlace" href="{{ route('paciente.citas.show', $cita->rescheduledFrom) }}">
                                        {{ $cita->rescheduledFrom->reference }}
                                    </a>
                                    · {{ $cita->rescheduledFrom->scheduled_at->format('d/m/Y · H:i') }}
                                </p>
                            </div>
                        </li>
                    @endif

                    @if ($cita->rescheduledTo->isNotEmpty())
                        <li>
                            <span class="paciente-linea-tiempo__punto" aria-hidden="true"></span>
                            <div>
                                <strong>Esta cita fue reprogramada</strong>
                                @foreach ($cita->rescheduledTo as $nuevaCita)
                                    <p>
                                        <a class="paciente-enlace" href="{{ route('paciente.citas.show', $nuevaCita) }}">
                                            {{ $nuevaCita->reference }}
                                        </a>
                                        · {{ $nuevaCita->scheduled_at->format('d/m/Y · H:i') }}
                                    </p>
                                @endforeach
                            </div>
                        </li>
                    @endif

                    @if ($cita->canceled_at)
                        <li>
                            <span class="paciente-linea-tiempo__punto" aria-hidden="true"></span>
                            <div>
                                <strong>Cita cancelada</strong>
                                <p>{{ $cita->canceled_at->format('d/m/Y · H:i') }}</p>
                            </div>
                        </li>
                    @endif
                </ul>

                <div class="paciente-nota">
                    <x-icono nombre="escudo" :tamano="18" />
                    <p>Si necesitas reprogramar o cancelar esta cita, comunícate con el área administrativa. El historial se conserva.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
