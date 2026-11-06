@extends('layouts.administrativo')

@section('titulo', 'Pacientes')
@section('descripcion', 'Registro de nuevos pacientes en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Gestión de pacientes</p>
                <h2>Nuevo paciente</h2>
                <p>Completa la información personal y de contacto para registrar al paciente.</p>
            </div>
            <a class="boton boton--secundario" href="{{ route('administrativo.pacientes.index') }}">
                <x-icono nombre="flecha-izquierda" :tamano="17" />
                Volver al listado
            </a>
        </div>

        @include('administrativo.pacientes.partials.formulario', ['paciente' => null])
    </div>
@endsection
