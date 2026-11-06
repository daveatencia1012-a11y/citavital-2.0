<section class="admin-panel estadisticas-panel" aria-label="{{ $titulo }}">
    <h3>{{ $titulo }}</h3>
    @if ($filas->sum('value') === 0)<p>No hay datos para este período y filtros.</p>@endif
    <div class="estadisticas-barras">
        @foreach ($filas as $fila)
            <div class="estadisticas-barra"><span>{{ $fila['label'] }}</span><progress aria-label="{{ $fila['label'] }}" data-estado="{{ $fila['status'] ?? '' }}" max="{{ max(1, $filas->max('value')) }}" value="{{ $fila['value'] }}">{{ $fila['value'] }}</progress><strong>{{ $fila['value'] }}</strong></div>
        @endforeach
    </div>
    <details><summary>Ver datos en tabla accesible</summary><div class="admin-tabla-contenedor"><table class="admin-tabla"><caption>{{ $titulo }}</caption><thead><tr><th scope="col">Categoría</th><th scope="col">Registros</th>@if ($desglose ?? false)<th scope="col">Programadas</th><th scope="col">Realizadas</th>@endif</tr></thead><tbody>
    @foreach ($filas as $fila)<tr><th scope="row">{{ $fila['label'] }}</th><td>{{ $fila['value'] }}</td>@if ($desglose ?? false)<td>{{ $fila['PROGRAMADA'] }}</td><td>{{ $fila['REALIZADA'] }}</td>@endif</tr>@endforeach
    </tbody></table></div></details>
</section>
