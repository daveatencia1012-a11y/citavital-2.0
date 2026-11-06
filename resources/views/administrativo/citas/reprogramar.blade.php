@extends('layouts.administrativo')
@section('titulo', 'Reprogramar cita')
@section('contenido')
<div class="admin-contenido citas-modulo">
    @include('administrativo.partials.alertas')
    <header class="admin-pagina__cabecera"><div><h2>Reprogramar {{ $cita->reference }}</h2><p>{{ $cita->patient->full_name }} · {{ $cita->doctor->full_name }} · {{ $cita->specialty->name }}</p></div><a href="{{ route('administrativo.citas.show', $cita) }}">Volver al detalle</a></header>
    <section class="admin-panel citas-panel"><h3>Consultar disponibilidad</h3><p>Se conserva el médico y la especialidad. La cita anterior permanece en el historial.</p>
        <form class="citas-filtros" method="post" action="{{ route('administrativo.citas.disponibilidad', $cita) }}">
            @csrf
            <label>Fecha · Bogotá<input class="campo__entrada" type="date" name="fecha" value="{{ $fecha }}" min="{{ now()->toDateString() }}" max="{{ now()->addDays(90)->toDateString() }}" required></label>
            <button class="boton boton--primario">Consultar turnos</button>
        </form>
        @if ($fecha)
            @if ($turnos->isEmpty())<p>No hay horarios disponibles para esta fecha.</p>@else
            <form class="citas-panel" method="post" action="{{ route('administrativo.citas.reprogramar', $cita) }}">
                @csrf
                <input type="hidden" name="fecha" value="{{ $fecha }}">
                <fieldset><legend>Nuevo horario · {{ $fecha }}</legend><div class="citas-turnos">@foreach ($turnos as $turno)<label><input type="radio" name="slot_id" value="{{ $turno->id }}" required> {{ $turno->starts_at->format('H:i') }} – {{ $turno->ends_at->format('H:i') }}</label>@endforeach</div></fieldset>
                <label>Observación<textarea class="campo__entrada" name="notes" maxlength="2000" rows="3">{{ old('notes') }}</textarea></label>
                <p>Al confirmar se vuelve a validar y bloquear el turno. Si otro paciente lo reservó, deberás elegir otro.</p>
                <button class="boton boton--primario">Confirmar reprogramación</button>
            </form>
            @endif
        @endif
    </section>
</div>
@endsection
