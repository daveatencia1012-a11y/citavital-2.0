@extends('layouts.paciente')

@section('titulo', 'Historial de citas')
@section('descripcion', 'Historial personal de citas médicas del paciente en CitaVital.')

@php($estadoSeleccionado = $estado)

@section('contenido')
    <div class="paciente-contenido">
        <header class="paciente-pagina__cabecera">
            <div>
                <p class="paciente-sobre">Trazabilidad</p>
                <h1>Historial de citas</h1>
                <p>Consulta las citas anteriores y los cambios de estado registrados. Los registros no se eliminan del historial.</p>
            </div>
            <a class="boton boton--secundario" href="{{ route('paciente.citas.solicitar') }}">
                <x-icono nombre="calendario" :tamano="18" />
                Solicitar cita
            </a>
        </header>

        <section class="paciente-panel">
            <form class="paciente-filtros" method="get" action="{{ route('paciente.citas.historial') }}">
                <div class="campo">
                    <label class="campo__etiqueta" for="historial-fecha">Filtrar por fecha</label>
                    <input class="campo__entrada paciente-campo-fecha" id="historial-fecha" name="date" type="date" value="{{ $fecha }}">
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="historial-estado">Filtrar por estado</label>
                    <select class="campo__entrada paciente-select" id="historial-estado" name="status">
                        <option value="todos" @selected($estadoSeleccionado === 'todos')>Todos los estados</option>
                        @foreach ($estados as $estadoCita)
                            <option value="{{ $estadoCita->value }}" @selected($estadoSeleccionado === $estadoCita->value)>{{ $estadoCita->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="paciente-filtros__acciones">
                    <button class="boton boton--primario" type="submit">
                        <x-icono nombre="buscar" :tamano="17" />
                        Filtrar
                    </button>
                    @if ($fecha !== '' || $estadoSeleccionado !== 'todos')
                        <a class="boton boton--secundario" href="{{ route('paciente.citas.historial') }}">Limpiar</a>
                    @endif
                </div>
            </form>

            @if ($citas->isEmpty())
                <div class="paciente-vacio">
                    <span class="paciente-vacio__icono"><x-icono nombre="historial" :tamano="28" /></span>
                    <h2>No hay citas que coincidan con el filtro.</h2>
                    <p>Cambia la fecha o el estado para consultar otros registros de tu historial.</p>
                </div>
            @else
                <div class="paciente-tabla-contenedor">
                    <table class="paciente-tabla">
                        <caption class="visualmente-oculto">Historial personal de citas</caption>
                        <thead>
                            <tr>
                                <th scope="col">Referencia</th>
                                <th scope="col">Especialidad</th>
                                <th scope="col">Médico</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Hora</th>
                                <th scope="col">Estado</th>
                                <th scope="col"><span class="visualmente-oculto">Detalle</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($citas as $cita)
                                <tr>
                                    <td>
                                        <span class="paciente-referencia">{{ $cita->reference }}</span>
                                        @if ($cita->rescheduledFrom)
                                            <small class="paciente-tabla__nota">Reprogramada desde {{ $cita->rescheduledFrom->reference }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $cita->specialty->name }}</td>
                                    <td>{{ $cita->doctor->full_name }}</td>
                                    <td>{{ $cita->scheduled_at->format('d/m/Y') }}</td>
                                    <td>{{ $cita->scheduled_at->format('H:i') }}</td>
                                    <td><x-estado-cita :estado="$cita->status" /></td>
                                    <td>
                                        <a class="paciente-enlace" href="{{ route('paciente.citas.show', $cita) }}">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('paciente.partials.paginacion', ['paginator' => $citas])
            @endif
        </section>
    </div>
@endsection
