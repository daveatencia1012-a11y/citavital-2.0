@extends('layouts.administrativo')

@section('titulo', 'Pacientes')
@section('descripcion', 'Edición de la información de un paciente en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Gestión de pacientes</p>
                <div class="admin-paciente-titulo">
                    <h2>Editar paciente</h2>
                    <span class="admin-estado admin-estado--{{ $paciente->is_active ? 'activo' : 'inactivo' }}">
                        {{ $paciente->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                <p>{{ $paciente->full_name }} · Cédula {{ $paciente->document_number }}</p>
            </div>
            <div class="admin-paciente-acciones">
                <a class="boton boton--secundario" href="{{ route('administrativo.pacientes.show', $paciente) }}">
                    <x-icono nombre="historial" :tamano="17" />
                    Ver historial
                </a>
                <a class="boton boton--secundario" href="{{ route('administrativo.pacientes.index') }}">
                    <x-icono nombre="flecha-izquierda" :tamano="17" />
                    Volver al listado
                </a>
            </div>
        </div>

        @include('administrativo.pacientes.partials.formulario', ['paciente' => $paciente])
    </div>
@endsection
