@extends('layouts.paciente')

@section('titulo', 'Próximas citas')
@section('descripcion', 'Citas futuras programadas del paciente en CitaVital.')

@section('contenido')
    <div class="paciente-contenido">
        <header class="paciente-pagina__cabecera">
            <div>
                <p class="paciente-sobre">Agenda personal</p>
                <h1>Próximas citas</h1>
                <p>Consulta tus citas programadas en orden cronológico. Todas las horas se muestran en la zona horaria America/Bogota.</p>
            </div>
            <a class="boton boton--primario" href="{{ route('paciente.citas.solicitar') }}">
                <x-icono nombre="calendario" :tamano="18" />
                Solicitar otra cita
            </a>
        </header>

        @if ($citas->isEmpty())
            <section class="paciente-panel">
                <div class="paciente-vacio">
                    <span class="paciente-vacio__icono"><x-icono nombre="calendario" :tamano="28" /></span>
                    <h2>No tienes próximas citas programadas.</h2>
                    <p>Puedes solicitar una cita seleccionando una especialidad, un médico y uno de los horarios disponibles.</p>
                    <a class="boton boton--primario" href="{{ route('paciente.citas.solicitar') }}">Solicitar cita</a>
                </div>
            </section>
        @else
            <div class="paciente-resumen-lista">
                <span class="paciente-contador">{{ $citas->count() }} {{ $citas->count() === 1 ? 'cita programada' : 'citas programadas' }}</span>
            </div>

            <ul class="paciente-lista-citas paciente-lista-citas--amplia">
                @foreach ($citas as $cita)
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
                            <div class="paciente-cita__datos">
                                <p><span>Médico</span>{{ $cita->doctor->full_name }}</p>
                                <p><span>Fecha</span>{{ $cita->scheduled_at->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}</p>
                                <p><span>Hora</span>{{ $cita->scheduled_at->format('H:i') }} – {{ $cita->ends_at->format('H:i') }}</p>
                            </div>
                        </div>
                        <a class="paciente-cita__ver" href="{{ route('paciente.citas.show', $cita) }}">Consultar detalle</a>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="paciente-nota paciente-nota--pagina">
            <x-icono nombre="reloj" :tamano="18" />
            <p>Si necesitas cambiar una cita, comunícate con el área administrativa. CitaVital no elimina registros del historial.</p>
        </div>
    </div>
@endsection
