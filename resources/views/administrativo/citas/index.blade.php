@extends('layouts.administrativo')
@section('titulo', 'Citas')
@section('contenido')
<div class="admin-contenido citas-modulo">
    @include('administrativo.partials.alertas')
    <header class="admin-pagina__cabecera"><div><p class="admin-pagina__sobre">Gestión de agenda</p><h2>Citas médicas</h2><p>Consulta, registra asistencia y gestiona cambios con trazabilidad.</p></div></header>
    <section class="admin-panel">
        <form method="get" action="{{ route('administrativo.citas.index') }}" class="citas-filtros">
            @foreach (['buscar' => 'Referencia, nombre o cédula', 'paciente' => 'Paciente', 'cedula' => 'Cédula'] as $campo => $etiqueta)
                <label>{{ $etiqueta }}<input class="campo__entrada" name="{{ $campo }}" value="{{ request($campo) }}" maxlength="120"></label>
            @endforeach
            <label>Médico<select class="campo__entrada" name="medico"><option value="">Todos</option>@foreach ($medicos as $medico)<option value="{{ $medico->id }}" @selected(request('medico') == $medico->id)>{{ $medico->full_name }}</option>@endforeach</select></label>
            <label>Especialidad<select class="campo__entrada" name="especialidad"><option value="">Todas</option>@foreach ($especialidades as $especialidad)<option value="{{ $especialidad->id }}" @selected(request('especialidad') == $especialidad->id)>{{ $especialidad->name }}</option>@endforeach</select></label>
            <label>Fecha<input class="campo__entrada" type="date" name="fecha" value="{{ request('fecha') }}"></label>
            <label>Estado<select class="campo__entrada" name="estado"><option value="">Todos</option>@foreach ($estados as $estado)<option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ $estado->label() }}</option>@endforeach</select></label>
            <label>Ordenar por<select class="campo__entrada" name="orden">@foreach (['scheduled_at' => 'Fecha y hora', 'reference' => 'Referencia', 'status' => 'Estado'] as $valor => $texto)<option value="{{ $valor }}" @selected(request('orden', 'scheduled_at') === $valor)>{{ $texto }}</option>@endforeach</select></label>
            <label>Dirección<select class="campo__entrada" name="direccion"><option value="asc" @selected(request('direccion', 'asc') === 'asc')>Ascendente</option><option value="desc" @selected(request('direccion') === 'desc')>Descendente</option></select></label>
            <div class="citas-acciones"><button class="boton boton--primario">Filtrar</button><a class="boton boton--secundario" href="{{ route('administrativo.citas.index') }}">Limpiar</a></div>
        </form>
        <div class="admin-tabla-contenedor"><table class="admin-tabla">
            <caption class="visualmente-oculto">Citas médicas registradas</caption>
            <thead><tr>@foreach (['Referencia', 'Paciente', 'Cédula', 'Médico', 'Especialidad', 'Fecha', 'Hora', 'Estado', 'Acciones'] as $titulo)<th scope="col">{{ $titulo }}</th>@endforeach</tr></thead>
            <tbody>@forelse ($citas as $cita)<tr>
                <td>{{ $cita->reference }}</td><td>{{ $cita->patient->full_name }}</td><td>{{ $cita->patient->document_number }}</td><td>{{ $cita->doctor->full_name }}</td><td>{{ $cita->specialty->name }}</td><td>{{ $cita->scheduled_at->format('d/m/Y') }}</td><td>{{ $cita->scheduled_at->format('H:i') }}</td><td><x-estado-cita :estado="$cita->status" /></td><td><a href="{{ route('administrativo.citas.show', $cita) }}">Ver y gestionar</a></td>
            </tr>@empty<tr><td colspan="9">No se encontraron citas con estos filtros.</td></tr>@endforelse</tbody>
        </table></div>
        @include('administrativo.partials.paginacion', ['paginator' => $citas])
    </section>
</div>
@endsection
