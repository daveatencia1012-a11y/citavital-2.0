@extends('layouts.administrativo')
@section('titulo', 'Seguimientos')
@section('contenido')
<div class="admin-contenido citas-modulo">
    @include('administrativo.partials.alertas')
    <header class="admin-pagina__cabecera"><div><p class="admin-pagina__sobre">Seguimiento telefónico manual</p><h2>Seguimiento de inasistencias</h2><p>Registra intentos, contacto y cierre. Cada gestión conserva fecha, administrador y observación.</p></div></header>
    <form method="get" class="citas-filtros" action="{{ route('administrativo.seguimientos.index') }}"><label>Estado<select class="campo__entrada" name="estado"><option value="todos" @selected($estado === 'todos')>Todos</option>@foreach ($estados as $opcion)<option value="{{ $opcion->value }}" @selected($estado === $opcion->value)>{{ str_replace('_', ' ', $opcion->value) }}</option>@endforeach</select></label><button class="boton boton--primario">Filtrar</button></form>
    @forelse ($seguimientos as $seguimiento)
        @php($cita = $seguimiento->appointment)
        <section class="admin-panel citas-panel">
            <header class="admin-panel__cabecera"><div><h3>{{ $cita->patient->full_name }}</h3><p>{{ $seguimiento->status->value }}</p>@if ($seguimiento->status === \App\Enums\FollowUpStatus::PENDING_CALL)<strong>Perdida – llamar al paciente</strong>@endif</div><a href="{{ route('administrativo.citas.show', $cita) }}">{{ $cita->reference }}</a></header>
            <dl class="citas-datos"><div><dt>Teléfono principal</dt><dd>{{ $cita->patient->primary_phone }}</dd></div><div><dt>Teléfono secundario</dt><dd>{{ $cita->patient->secondary_phone ?: 'Sin registrar' }}</dd></div><div><dt>Fecha de la cita</dt><dd>{{ $cita->scheduled_at->format('d/m/Y H:i') }}</dd></div><div><dt>Médico</dt><dd>{{ $cita->doctor->full_name }}</dd></div></dl>
            <details><summary>Observaciones registradas</summary><p class="citas-notas">{{ $seguimiento->notes }}</p><p>Última gestión: {{ $seguimiento->managedBy?->name ?? 'Sin asignar' }} · {{ $seguimiento->updated_at->format('d/m/Y H:i') }}</p></details>
            @if (! in_array($seguimiento->status, [\App\Enums\FollowUpStatus::CLOSED, \App\Enums\FollowUpStatus::RESCHEDULED], true))
                <form class="citas-panel" method="post" action="{{ route('administrativo.seguimientos.update', $seguimiento) }}">
                    @csrf @method('PATCH')
                    <label>Gestión<select class="campo__entrada" name="action"><option value="call">Registrar intento de llamada</option><option value="contact">Paciente contactado</option><option value="note">Agregar observación</option><option value="close">Cerrar seguimiento</option></select></label>
                    <label>Observación<textarea class="campo__entrada" name="notes" rows="2" maxlength="2000" required></textarea></label>
                    <div class="citas-acciones"><button class="boton boton--primario">Guardar gestión</button><a class="boton boton--secundario" href="{{ route('administrativo.citas.edit', $cita) }}">Reprogramar cita</a></div>
                </form>
            @endif
        </section>
    @empty<div class="admin-vacio"><h3>No hay seguimientos con este estado.</h3></div>@endforelse
    @include('administrativo.partials.paginacion', ['paginator' => $seguimientos])
</div>
@endsection
