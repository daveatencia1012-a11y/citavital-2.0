@extends('layouts.administrativo')
@section('titulo', 'Estadísticas')
@section('contenido')
<div class="admin-contenido estadisticas">
    @include('administrativo.partials.alertas')
    <header class="admin-pagina__cabecera"><div><p class="admin-pagina__sobre">Análisis administrativo</p><h2>Estadísticas</h2><p>Información agregada de MySQL · Hora de Bogotá</p></div></header>
    <section class="admin-panel estadisticas-panel">
        <form class="estadisticas-filtros" method="get" action="{{ route('administrativo.estadisticas.index') }}">
            <label>Período<select class="campo__entrada" name="periodo">@foreach (['hoy' => 'Hoy', '7dias' => 'Últimos 7 días', '30dias' => 'Últimos 30 días', 'mes' => 'Mes actual', 'personalizado' => 'Rango personalizado'] as $key => $label)<option value="{{ $key }}" @selected(old('periodo', $periodo) === $key)>{{ $label }}</option>@endforeach</select></label>
            <label>Fecha inicial<input class="campo__entrada" type="date" name="desde" value="{{ old('desde', $desde) }}"></label>
            <label>Fecha final<input class="campo__entrada" type="date" name="hasta" value="{{ old('hasta', $hasta) }}"></label>
            <label>Especialidad<select class="campo__entrada" name="especialidad"><option value="">Todas</option>@foreach ($especialidades as $especialidad)<option value="{{ $especialidad->id }}" @selected(request('especialidad') == $especialidad->id)>{{ $especialidad->name }}</option>@endforeach</select></label>
            <label>Médico<select class="campo__entrada" name="medico"><option value="">Todos</option>@foreach ($medicos as $medico)<option value="{{ $medico->id }}" @selected(request('medico') == $medico->id)>{{ $medico->full_name }}</option>@endforeach</select></label>
            <button class="boton boton--primario">Consultar</button><a href="{{ route('administrativo.estadisticas.index') }}">Restablecer filtros</a>
        </form>
        <p class="estadisticas-criterio">Para usar las fechas escritas, selecciona «Rango personalizado» (máximo cinco años). Los períodos rápidos calculan sus fechas automáticamente; hoy se incluye en los últimos 7 y 30 días. Mes actual incluye todo el mes.</p>
    </section>
    <section class="admin-panel estadisticas-panel">
        <h3>Período: {{ $desde }} al {{ $hasta }}</h3>
        <p class="estadisticas-criterio">Criterio: fecha de atención (<code>scheduled_at</code>), no fecha de creación de la reserva. Los seguimientos se atribuyen a la fecha de atención de su cita y muestran su estado actual, no la fecha de llamada. Especialidad y médico filtran todas las cifras del período.</p>
        <p class="estadisticas-criterio">Cada referencia cuenta como un registro. Las referencias REPROGRAMADA son históricas, no atenciones adicionales. Las inasistencias reprogramadas conservan NO_ASISTIO. Programadas + realizadas: <strong>{{ $effective }}</strong>; esta suma excluye cancelaciones, inasistencias y referencias sustituidas. Solo REALIZADA representa una atención completada.</p>
    </section>
    <div class="admin-indicadores">
        <x-tarjeta-indicador clave="total" etiqueta="Total de citas registradas" :valor="$total" icono="agenda" detalle="Referencias del período, incluidas históricas" />
        @foreach ($states->whereIn('status', ['PROGRAMADA', 'REALIZADA', 'CANCELADA', 'NO_ASISTIO']) as $state)
            <x-tarjeta-indicador :clave="$state['status']" :etiqueta="'Citas: '.$state['label']" :valor="$state['value']" icono="calendario" detalle="Fecha de atención dentro del período" />
        @endforeach
        <x-tarjeta-indicador clave="activePatients" etiqueta="Pacientes activos" :valor="$activePatients" icono="usuarios" detalle="Global actual · no depende de filtros" />
        <x-tarjeta-indicador clave="activeDoctors" etiqueta="Médicos activos" :valor="$activeDoctors" icono="estetoscopio" detalle="Global actual · no depende de filtros" />
        <x-tarjeta-indicador clave="pending" etiqueta="Seguimientos pendientes" :valor="$pending" icono="telefono" detalle="Pendientes de citas del período" />
    </div>
    <div class="estadisticas-graficos">
        @include('administrativo.estadisticas.grafico', ['titulo' => 'Distribución de referencias por estado', 'filas' => $states])
        @include('administrativo.estadisticas.grafico', ['titulo' => 'Citas registradas por especialidad', 'filas' => $specialties])
        @include('administrativo.estadisticas.grafico', ['titulo' => 'Citas registradas por médico', 'filas' => $doctors, 'desglose' => true])
        @include('administrativo.estadisticas.grafico', ['titulo' => 'Evolución por '.$granularity.' de atención', 'filas' => $evolution, 'desglose' => false])
        @include('administrativo.estadisticas.grafico', ['titulo' => 'Inasistencias y estados de seguimiento', 'filas' => $followUps, 'desglose' => false])
    </div>
    <section class="admin-panel estadisticas-panel"><h3>Resumen por especialidad · período seleccionado</h3><p>El total incluye las referencias reprogramadas; se muestran separadas para conciliar las cifras.</p>
        <div class="admin-tabla-contenedor"><table class="admin-tabla"><caption>Registros según los filtros seleccionados</caption><thead><tr><th>Especialidad</th><th>Total de citas</th><th>Programadas</th><th>Realizadas</th><th>Canceladas</th><th>Inasistencias</th><th>Reprogramadas</th></tr></thead><tbody>
            @forelse ($specialties as $row)<tr><th scope="row">{{ $row['label'] }}</th><td>{{ $row['value'] }}</td>@foreach (['PROGRAMADA', 'REALIZADA', 'CANCELADA', 'NO_ASISTIO', 'REPROGRAMADA'] as $status)<td>{{ $row[$status] }}</td>@endforeach</tr>@empty<tr><td colspan="7">No hay citas para estos filtros.</td></tr>@endforelse
        </tbody><tfoot><tr><th scope="row">Total del período</th><td>{{ $total }}</td>@foreach (['PROGRAMADA', 'REALIZADA', 'CANCELADA', 'NO_ASISTIO', 'REPROGRAMADA'] as $status)<td>{{ $states->firstWhere('status', $status)['value'] }}</td>@endforeach</tr></tfoot></table></div>
    </section>
    <section class="admin-panel estadisticas-panel"><h3>Histórico global · sin filtros</h3><p>Total acumulado de referencias: <strong>{{ $history->sum('value') }}</strong>. Estados actuales de todos los registros, sin limitar fecha, médico o especialidad.</p><ul>@foreach ($history as $state)<li>{{ $state['label'] }}: {{ $state['value'] }}</li>@endforeach</ul></section>
</div>
@endsection
