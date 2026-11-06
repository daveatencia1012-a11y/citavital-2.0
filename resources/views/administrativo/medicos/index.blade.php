@extends('layouts.administrativo')

@section('titulo', 'Médicos')
@section('descripcion', 'Gestión administrativa de médicos y sus especialidades en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Cuerpo médico</p>
                <h2>Médicos</h2>
                <p>Registra profesionales, asigna sus especialidades y controla su disponibilidad para nuevas reservas.</p>
            </div>
            <a class="boton boton--primario" href="{{ route('administrativo.medicos.create') }}">
                <x-icono nombre="estetoscopio" :tamano="18" />
                Nuevo médico
            </a>
        </div>

        <section class="admin-panel" aria-labelledby="titulo-listado-medicos">
            <header class="admin-panel__cabecera">
                <div>
                    <p class="admin-panel__sobre">Directorio médico</p>
                    <h3 id="titulo-listado-medicos">Listado de médicos</h3>
                    <p>Busca por código o nombre, filtra por estado o especialidad. Los médicos inactivos no admiten nuevas reservas.</p>
                </div>
                <span class="admin-panel__contador">
                    {{ $medicos->total() }} {{ $medicos->total() === 1 ? 'médico' : 'médicos' }}
                </span>
            </header>

            <div class="admin-toolbar">
                <form class="admin-filtros admin-filtros--medicos" method="GET" action="{{ route('administrativo.medicos.index') }}" role="search">
                    <div class="campo__control admin-buscador__control">
                        <x-icono nombre="buscar" :tamano="18" class="campo__icono" />
                        <input
                            class="campo__entrada"
                            type="search"
                            name="buscar"
                            value="{{ $busqueda }}"
                            placeholder="Buscar por código, nombres o apellidos"
                            aria-label="Buscar médicos por código, nombres o apellidos"
                        >
                    </div>
                    <div class="admin-filtro">
                        <label class="visualmente-oculto" for="estado-medicos">Filtrar por estado</label>
                        <select class="campo__entrada admin-filtro__select" id="estado-medicos" name="estado">
                            <option value="todos" @selected($estado === 'todos')>Todos los estados</option>
                            <option value="activos" @selected($estado === 'activos')>Solo activos</option>
                            <option value="inactivos" @selected($estado === 'inactivos')>Solo inactivos</option>
                        </select>
                    </div>
                    <div class="admin-filtro">
                        <label class="visualmente-oculto" for="especialidad-medicos">Filtrar por especialidad</label>
                        <select class="campo__entrada admin-filtro__select" id="especialidad-medicos" name="especialidad">
                            <option value="">Todas las especialidades</option>
                            @foreach ($especialidades as $especialidad)
                                <option value="{{ $especialidad->id }}" @selected($especialidadFiltro === $especialidad->id)>
                                    {{ $especialidad->name }}{{ $especialidad->is_active ? '' : ' (inactiva)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button class="boton boton--primario" type="submit">Filtrar</button>
                    @if ($busqueda !== '' || $estado !== 'todos' || $especialidadFiltro > 0)
                        <a class="boton boton--secundario" href="{{ route('administrativo.medicos.index') }}">Limpiar</a>
                    @endif
                </form>
            </div>

            @if ($medicos->isEmpty())
                <div class="admin-vacio">
                    <span class="admin-vacio__icono">
                        <x-icono nombre="estetoscopio" :tamano="26" />
                    </span>
                    @if ($busqueda !== '' || $estado !== 'todos' || $especialidadFiltro > 0)
                        <h4>No encontramos médicos con esos filtros.</h4>
                        <p>Prueba con otro código, nombre o especialidad, o limpia los filtros para ver el directorio completo.</p>
                        <a class="boton boton--secundario" href="{{ route('administrativo.medicos.index') }}">Limpiar filtros</a>
                    @else
                        <h4>No hay médicos registrados.</h4>
                        <p>Registra el primer médico y asigna al menos una especialidad para comenzar.</p>
                        <a class="boton boton--primario" href="{{ route('administrativo.medicos.create') }}">Registrar médico</a>
                    @endif
                </div>
            @else
                <div class="admin-tabla-contenedor">
                    <table class="admin-tabla admin-tabla--medicos">
                        <caption class="visualmente-oculto">Médicos registrados en CitaVital</caption>
                        <thead>
                            <tr>
                                <th scope="col">Código interno</th>
                                <th scope="col">Médico</th>
                                <th scope="col">Especialidades</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Citas futuras</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($medicos as $medico)
                                <tr>
                                    <td><span class="admin-codigo">{{ $medico->internal_code }}</span></td>
                                    <td>
                                        <span class="admin-tabla__principal">{{ $medico->full_name }}</span>
                                    </td>
                                    <td>
                                        @if ($medico->specialties->isEmpty())
                                            <span class="admin-tabla__secundario">Sin especialidades</span>
                                        @else
                                            <div class="admin-etiquetas">
                                                @foreach ($medico->specialties as $especialidad)
                                                    <span class="admin-etiqueta {{ $especialidad->is_active ? '' : 'admin-etiqueta--inactiva' }}">
                                                        {{ $especialidad->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td><x-estado-registro :activo="$medico->is_active" /></td>
                                    <td>
                                        @if ($medico->future_appointments_count > 0)
                                            <span class="admin-badge admin-badge--advertencia">
                                                {{ $medico->future_appointments_count }}
                                                {{ $medico->future_appointments_count === 1 ? 'cita' : 'citas' }}
                                            </span>
                                        @else
                                            <span class="admin-tabla__secundario">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="admin-acciones">
                                            <a
                                                class="admin-accion"
                                                href="{{ route('administrativo.medicos.edit', $medico) }}"
                                                title="Editar médico"
                                                aria-label="Editar a {{ $medico->full_name }}"
                                            >
                                                <x-icono nombre="lapiz" :tamano="17" />
                                            </a>

                                            @if ($medico->is_active)
                                                <form
                                                    method="POST"
                                                    action="{{ route('administrativo.medicos.estado', $medico) }}"
                                                    data-confirm="¿Desactivar a {{ $medico->full_name }}?{{ $medico->future_appointments_count > 0 ? ' Conserva '.$medico->future_appointments_count.' citas futuras y no se cancelarán automáticamente.' : '' }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        class="admin-accion admin-accion--peligro"
                                                        type="submit"
                                                        title="Desactivar médico"
                                                        aria-label="Desactivar a {{ $medico->full_name }}"
                                                    >
                                                        <x-icono nombre="cerrar" :tamano="17" />
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('administrativo.medicos.estado', $medico) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        class="admin-accion admin-accion--exito"
                                                        type="submit"
                                                        title="Reactivar médico"
                                                        aria-label="Reactivar a {{ $medico->full_name }}"
                                                    >
                                                        <x-icono nombre="check-circulo" :tamano="17" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('administrativo.partials.paginacion', ['paginator' => $medicos])
            @endif
        </section>
    </div>
@endsection
