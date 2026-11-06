@extends('layouts.administrativo')

@section('titulo', 'Médicos')
@section('descripcion', 'Registro de nuevos médicos en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Cuerpo médico</p>
                <h2>Nuevo médico</h2>
                <p>Registra los datos profesionales y asigna las especialidades que podrá atender.</p>
            </div>
            <a class="boton boton--secundario" href="{{ route('administrativo.medicos.index') }}">
                <x-icono nombre="flecha-izquierda" :tamano="17" />
                Volver al listado
            </a>
        </div>

        @include('administrativo.medicos.partials.formulario', ['medico' => null])
    </div>
@endsection
