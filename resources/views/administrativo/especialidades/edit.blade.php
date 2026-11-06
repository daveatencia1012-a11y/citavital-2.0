@extends('layouts.administrativo')

@section('titulo', 'Especialidades')
@section('descripcion', 'Edición de una especialidad médica en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Catálogo clínico</p>
                <div class="admin-paciente-titulo">
                    <h2>Editar especialidad</h2>
                    <x-estado-registro :activo="$especialidad->is_active" />
                </div>
                <p>ESP-{{ str_pad((string) $especialidad->id, 3, '0', STR_PAD_LEFT) }} · {{ $especialidad->name }}</p>
            </div>
            <a class="boton boton--secundario" href="{{ route('administrativo.especialidades.index') }}">
                <x-icono nombre="flecha-izquierda" :tamano="17" />
                Volver al listado
            </a>
        </div>

        @include('administrativo.especialidades.partials.formulario', ['especialidad' => $especialidad])
    </div>
@endsection
