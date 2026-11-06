@extends('layouts.administrativo')

@section('titulo', 'Médicos')
@section('descripcion', 'Edición de la información de un médico en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Cuerpo médico</p>
                <div class="admin-paciente-titulo">
                    <h2>Editar médico</h2>
                    <x-estado-registro :activo="$medico->is_active" />
                </div>
                <p>{{ $medico->internal_code }} · {{ $medico->full_name }}</p>
            </div>
            <div class="admin-paciente-acciones">
                @if ($medico->is_active)
                    <a class="boton boton--secundario" href="{{ route('administrativo.horarios.index', ['doctor' => $medico->id]) }}">
                        <x-icono nombre="reloj" :tamano="17" />
                        Ver horarios
                    </a>
                @endif
                <a class="boton boton--secundario" href="{{ route('administrativo.medicos.index') }}">
                    <x-icono nombre="flecha-izquierda" :tamano="17" />
                    Volver al listado
                </a>
            </div>
        </div>

        @include('administrativo.medicos.partials.formulario', ['medico' => $medico])
    </div>
@endsection
