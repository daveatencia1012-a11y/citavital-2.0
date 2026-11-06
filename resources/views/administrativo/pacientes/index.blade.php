@extends('layouts.administrativo')

@section('titulo', 'Pacientes')
@section('descripcion', 'Gestión administrativa de pacientes de CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Gestión de pacientes</p>
                <h2>Pacientes</h2>
                <p>Registra, consulta y actualiza la información del directorio de pacientes.</p>
            </div>
            <a class="boton boton--primario" href="{{ route('administrativo.pacientes.create') }}">
                <x-icono nombre="usuarios" :tamano="18" />
                Nuevo paciente
            </a>
        </div>

        <section class="admin-panel" aria-labelledby="titulo-listado-pacientes">
            <header class="admin-panel__cabecera">
                <div>
                    <p class="admin-panel__sobre">Directorio</p>
                    <h3 id="titulo-listado-pacientes">Listado de pacientes</h3>
                    <p>Consulta la información registrada y accede al historial de citas de cada paciente.</p>
                </div>
                <span class="admin-panel__contador">
                    {{ $pacientes->total() }} {{ $pacientes->total() === 1 ? 'paciente' : 'pacientes' }}
                </span>
            </header>

            <div class="admin-toolbar">
                <form class="admin-buscador" method="GET" action="{{ route('administrativo.pacientes.index') }}" role="search">
                    <div class="campo__control admin-buscador__control">
                        <x-icono nombre="buscar" :tamano="18" class="campo__icono" />
                        <input
                            class="campo__entrada"
                            type="search"
                            name="buscar"
                            value="{{ $busqueda }}"
                            placeholder="Buscar por cédula, nombres o apellidos"
                            aria-label="Buscar pacientes por cédula, nombres o apellidos"
                        >
                    </div>
                    <button class="boton boton--primario" type="submit">Buscar</button>
                    @if ($busqueda !== '')
                        <a class="boton boton--secundario" href="{{ route('administrativo.pacientes.index') }}">Limpiar</a>
                    @endif
                </form>
            </div>

            @if ($pacientes->isEmpty())
                <div class="admin-vacio">
                    <span class="admin-vacio__icono">
                        <x-icono nombre="usuarios" :tamano="26" />
                    </span>
                    @if ($busqueda !== '')
                        <h4>No encontramos pacientes con ese criterio.</h4>
                        <p>Prueba con otra cédula, nombre o apellido, o limpia la búsqueda para ver el directorio completo.</p>
                        <a class="boton boton--secundario" href="{{ route('administrativo.pacientes.index') }}">Limpiar búsqueda</a>
                    @else
                        <h4>No hay pacientes registrados.</h4>
                        <p>Registra el primer paciente para comenzar a construir el directorio administrativo.</p>
                        <a class="boton boton--primario" href="{{ route('administrativo.pacientes.create') }}">Registrar paciente</a>
                    @endif
                </div>
            @else
                <div class="admin-tabla-contenedor">
                    <table class="admin-tabla admin-tabla--pacientes">
                        <caption class="visualmente-oculto">Pacientes registrados en CitaVital</caption>
                        <thead>
                            <tr>
                                <th scope="col">Cédula</th>
                                <th scope="col">Nombres</th>
                                <th scope="col">Apellidos</th>
                                <th scope="col">Edad</th>
                                <th scope="col">Seguro</th>
                                <th scope="col">Teléfono</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pacientes as $paciente)
                                <tr>
                                    <td><span class="admin-documento">{{ $paciente->document_number }}</span></td>
                                    <td><span class="admin-tabla__paciente">{{ $paciente->first_name }}</span></td>
                                    <td>{{ $paciente->last_name }}</td>
                                    <td><span class="admin-tabla__edad">{{ $paciente->age }} años</span></td>
                                    <td><x-tipo-seguro :tipo="$paciente->insurance_type" /></td>
                                    <td>
                                        <a class="admin-telefono" href="tel:{{ $paciente->primary_phone }}">{{ $paciente->primary_phone }}</a>
                                    </td>
                                    <td>
                                        <span class="admin-estado admin-estado--{{ $paciente->is_active ? 'activo' : 'inactivo' }}">
                                            {{ $paciente->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="admin-acciones">
                                            <a
                                                class="admin-accion"
                                                href="{{ route('administrativo.pacientes.show', $paciente) }}"
                                                title="Ver historial"
                                                aria-label="Ver historial de {{ $paciente->full_name }}"
                                            >
                                                <x-icono nombre="historial" :tamano="17" />
                                            </a>
                                            <a
                                                class="admin-accion"
                                                href="{{ route('administrativo.pacientes.edit', $paciente) }}"
                                                title="Editar paciente"
                                                aria-label="Editar a {{ $paciente->full_name }}"
                                            >
                                                <x-icono nombre="lapiz" :tamano="17" />
                                            </a>

                                            @if ($paciente->is_active)
                                                <form
                                                    method="POST"
                                                    action="{{ route('administrativo.pacientes.estado', $paciente) }}"
                                                    data-confirm="¿Desactivar a {{ $paciente->full_name }}? El paciente conservará sus citas e historial."
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        class="admin-accion admin-accion--peligro"
                                                        type="submit"
                                                        title="Desactivar paciente"
                                                        aria-label="Desactivar a {{ $paciente->full_name }}"
                                                    >
                                                        <x-icono nombre="cerrar" :tamano="17" />
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('administrativo.pacientes.estado', $paciente) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        class="admin-accion admin-accion--exito"
                                                        type="submit"
                                                        title="Reactivar paciente"
                                                        aria-label="Reactivar a {{ $paciente->full_name }}"
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

                @include('administrativo.partials.paginacion', ['paginator' => $pacientes])
            @endif
        </section>
    </div>
@endsection
