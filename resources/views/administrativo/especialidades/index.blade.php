@extends('layouts.administrativo')

@section('titulo', 'Especialidades')
@section('descripcion', 'Gestión administrativa de especialidades médicas de CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Catálogo clínico</p>
                <h2>Especialidades</h2>
                <p>Administra las especialidades disponibles para la asignación de médicos y la programación de citas.</p>
            </div>
            <a class="boton boton--primario" href="{{ route('administrativo.especialidades.create') }}">
                <x-icono nombre="institucion" :tamano="18" />
                Nueva especialidad
            </a>
        </div>

        <section class="admin-panel" aria-labelledby="titulo-listado-especialidades">
            <header class="admin-panel__cabecera">
                <div>
                    <p class="admin-panel__sobre">Catálogo</p>
                    <h3 id="titulo-listado-especialidades">Listado de especialidades</h3>
                    <p>Busca por nombre o descripción y filtra por estado. Las especialidades inactivas no admiten nuevas reservas.</p>
                </div>
                <span class="admin-panel__contador">
                    {{ $especialidades->total() }} {{ $especialidades->total() === 1 ? 'especialidad' : 'especialidades' }}
                </span>
            </header>

            <div class="admin-toolbar">
                <form class="admin-filtros" method="GET" action="{{ route('administrativo.especialidades.index') }}" role="search">
                    <div class="campo__control admin-buscador__control">
                        <x-icono nombre="buscar" :tamano="18" class="campo__icono" />
                        <input
                            class="campo__entrada"
                            type="search"
                            name="buscar"
                            value="{{ $busqueda }}"
                            placeholder="Buscar por nombre o descripción"
                            aria-label="Buscar especialidades por nombre o descripción"
                        >
                    </div>
                    <div class="admin-filtro">
                        <label class="visualmente-oculto" for="estado-especialidades">Filtrar por estado</label>
                        <select class="campo__entrada admin-filtro__select" id="estado-especialidades" name="estado">
                            <option value="todos" @selected($estado === 'todos')>Todos los estados</option>
                            <option value="activos" @selected($estado === 'activos')>Solo activos</option>
                            <option value="inactivos" @selected($estado === 'inactivos')>Solo inactivos</option>
                        </select>
                    </div>
                    <button class="boton boton--primario" type="submit">Filtrar</button>
                    @if ($busqueda !== '' || $estado !== 'todos')
                        <a class="boton boton--secundario" href="{{ route('administrativo.especialidades.index') }}">Limpiar</a>
                    @endif
                </form>
            </div>

            @if ($especialidades->isEmpty())
                <div class="admin-vacio">
                    <span class="admin-vacio__icono">
                        <x-icono nombre="institucion" :tamano="26" />
                    </span>
                    @if ($busqueda !== '' || $estado !== 'todos')
                        <h4>No encontramos especialidades con esos filtros.</h4>
                        <p>Prueba con otro nombre o cambia el estado seleccionado.</p>
                        <a class="boton boton--secundario" href="{{ route('administrativo.especialidades.index') }}">Limpiar filtros</a>
                    @else
                        <h4>No hay especialidades registradas.</h4>
                        <p>Registra la primera especialidad para comenzar a organizar el catálogo clínico.</p>
                        <a class="boton boton--primario" href="{{ route('administrativo.especialidades.create') }}">Registrar especialidad</a>
                    @endif
                </div>
            @else
                <div class="admin-tabla-contenedor">
                    <table class="admin-tabla admin-tabla--especialidades">
                        <caption class="visualmente-oculto">Especialidades registradas en CitaVital</caption>
                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Descripción</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Citas futuras</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($especialidades as $especialidad)
                                <tr>
                                    <td>
                                        <span class="admin-codigo">
                                            ESP-{{ str_pad((string) $especialidad->id, 3, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </td>
                                    <td><span class="admin-tabla__principal">{{ $especialidad->name }}</span></td>
                                    <td>
                                        @if ($especialidad->description)
                                            <span class="admin-tabla__descripcion" title="{{ $especialidad->description }}">
                                                {{ \Illuminate\Support\Str::limit($especialidad->description, 90) }}
                                            </span>
                                        @else
                                            <span class="admin-tabla__secundario">Sin descripción</span>
                                        @endif
                                    </td>
                                    <td><x-estado-registro :activo="$especialidad->is_active" /></td>
                                    <td>
                                        @if ($especialidad->future_appointments_count > 0)
                                            <span class="admin-badge admin-badge--advertencia">
                                                {{ $especialidad->future_appointments_count }}
                                                {{ $especialidad->future_appointments_count === 1 ? 'cita' : 'citas' }}
                                            </span>
                                        @else
                                            <span class="admin-tabla__secundario">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="admin-acciones">
                                            <a
                                                class="admin-accion"
                                                href="{{ route('administrativo.especialidades.edit', $especialidad) }}"
                                                title="Editar especialidad"
                                                aria-label="Editar {{ $especialidad->name }}"
                                            >
                                                <x-icono nombre="lapiz" :tamano="17" />
                                            </a>

                                            @if ($especialidad->is_active)
                                                <form
                                                    method="POST"
                                                    action="{{ route('administrativo.especialidades.estado', $especialidad) }}"
                                                    data-confirm="¿Desactivar {{ $especialidad->name }}?{{ $especialidad->future_appointments_count > 0 ? ' Conserva '.$especialidad->future_appointments_count.' citas futuras y no se cancelarán automáticamente.' : '' }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        class="admin-accion admin-accion--peligro"
                                                        type="submit"
                                                        title="Desactivar especialidad"
                                                        aria-label="Desactivar {{ $especialidad->name }}"
                                                    >
                                                        <x-icono nombre="cerrar" :tamano="17" />
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('administrativo.especialidades.estado', $especialidad) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        class="admin-accion admin-accion--exito"
                                                        type="submit"
                                                        title="Reactivar especialidad"
                                                        aria-label="Reactivar {{ $especialidad->name }}"
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

                @include('administrativo.partials.paginacion', ['paginator' => $especialidades])
            @endif
        </section>
    </div>
@endsection
