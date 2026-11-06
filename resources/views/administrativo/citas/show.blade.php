@extends('layouts.administrativo')
@section('titulo', 'Detalle de cita')
@section('contenido')
<div class="admin-contenido citas-modulo">
    @include('administrativo.partials.alertas')
    <header class="admin-pagina__cabecera"><div><p class="admin-pagina__sobre">Registro de atención</p><h2>{{ $cita->reference }}</h2><x-estado-cita :estado="$cita->status" /></div><a class="boton boton--secundario" href="{{ route('administrativo.citas.index') }}">Volver a citas</a></header>
    <section class="admin-panel citas-panel">
        <h3>Información de la cita</h3>
        <dl class="citas-datos">
            <div><dt>Paciente</dt><dd>{{ $cita->patient->full_name }}</dd></div><div><dt>Cédula</dt><dd>{{ $cita->patient->document_number }}</dd></div>
            <div><dt>Teléfonos</dt><dd>{{ $cita->patient->primary_phone }} / {{ $cita->patient->secondary_phone ?: 'Sin teléfono secundario' }}</dd></div>
            <div><dt>Médico</dt><dd>{{ $cita->doctor->full_name }}</dd></div><div><dt>Especialidad</dt><dd>{{ $cita->specialty->name }}</dd></div>
            <div><dt>Fecha y horario · Bogotá</dt><dd>{{ $cita->scheduled_at->format('d/m/Y H:i') }} – {{ $cita->ends_at->format('H:i') }}</dd></div>
        </dl>
        @if ($cita->cancellation_reason)<p>Motivo de cancelación: {{ $cita->cancellation_reason }}</p>@endif
        @if ($cita->rescheduledFrom)<p>Origen: <a href="{{ route('administrativo.citas.show', $cita->rescheduledFrom) }}">{{ $cita->rescheduledFrom->reference }}</a></p>@endif
        @foreach ($cita->rescheduledTo as $nueva)<p>Nueva programación: <a href="{{ route('administrativo.citas.show', $nueva) }}">{{ $nueva->reference }}</a></p>@endforeach
    </section>
    @if ($cita->isScheduled())
        <section class="admin-panel citas-panel"><h3>Operaciones administrativas</h3>
            <p>Asistencia e inasistencia se habilitan al terminar el horario de atención. Cada cambio queda auditado.</p>
            <div class="citas-operaciones">
            @foreach (['asistencia' => 'Marcar realizada', 'inasistencia' => 'Marcar inasistencia', 'cancelar' => 'Cancelar cita'] as $accion => $texto)
                @if ($accion === 'cancelar' || $cita->ends_at->lte(now()))
                <form method="post" action="{{ route('administrativo.citas.'.$accion, $cita) }}" data-confirm="¿{{ $texto }}?">
                    @csrf @method('PATCH')
                    <label for="nota-{{ $accion }}">Observación · {{ $texto }}</label><textarea class="campo__entrada" id="nota-{{ $accion }}" name="notes" maxlength="2000" rows="2"></textarea>
                    <button class="boton boton--secundario" type="submit">{{ $texto }}</button>
                </form>
                @endif
            @endforeach
            </div>
        </section>
    @endif
    @if (in_array($cita->status, [\App\Enums\AppointmentStatus::SCHEDULED, \App\Enums\AppointmentStatus::MISSED], true) && $cita->rescheduledTo->isEmpty())
        <a class="boton boton--primario" href="{{ route('administrativo.citas.edit', $cita) }}">Reprogramar cita</a>
    @endif
    <section class="admin-panel citas-panel"><h3>Seguimientos asociados</h3>
        @forelse ($cita->followUps as $seguimiento)
            <article class="citas-seguimiento"><strong>{{ $seguimiento->status->value }}</strong>
                @if ($seguimiento->status === \App\Enums\FollowUpStatus::PENDING_CALL)<p>Perdida – llamar al paciente</p>@endif
                <p class="citas-notas">{{ $seguimiento->notes }}</p><p>Gestionado por: {{ $seguimiento->managedBy?->name ?? 'Sin asignar' }}</p>
                <a href="{{ route('administrativo.seguimientos.index', ['estado' => $seguimiento->status->value]) }}">Gestionar seguimiento</a>
            </article>
        @empty<p>Sin seguimientos asociados.</p>@endforelse
    </section>
    <section class="admin-panel citas-panel"><h3>Historial de modificaciones</h3>
        <div class="admin-tabla-contenedor"><table class="admin-tabla"><thead><tr><th>Fecha · Bogotá</th><th>Administrador</th><th>Acción</th><th>Anterior</th><th>Nuevo</th><th>Observación</th></tr></thead>
            <tbody>@forelse ($auditorias as $auditoria)<tr><td>{{ $auditoria->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $auditoria->actor?->name ?? 'Portal / sistema' }}</td><td>{{ $auditoria->operation }}</td><td>{{ $auditoria->old_status ?? '—' }}</td><td>{{ $auditoria->new_status ?? '—' }}</td><td>{{ $auditoria->notes ?? '—' }}</td></tr>@empty<tr><td colspan="6">Sin modificaciones registradas.</td></tr>@endforelse</tbody>
        </table></div>
    </section>
</div>
@endsection
