@extends('layouts.administrativo')

@section('titulo', 'Especialidades')
@section('descripcion', 'Registro de nuevas especialidades médicas en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Catálogo clínico</p>
                <h2>Nueva especialidad</h2>
                <p>Registra una especialidad para asignarla a los médicos y habilitarla en la programación de citas.</p>
            </div>
            <a class="boton boton--secundario" href="{{ route('administrativo.especialidades.index') }}">
                <x-icono nombre="flecha-izquierda" :tamano="17" />
                Volver al listado
            </a>
        </div>

        @include('administrativo.especialidades.partials.formulario', ['especialidad' => null])
    </div>
@endsection
