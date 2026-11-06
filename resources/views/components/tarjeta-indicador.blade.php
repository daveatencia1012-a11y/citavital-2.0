@props([
    'clave',
    'etiqueta',
    'valor',
    'icono',
    'tono' => 'azul',
    'detalle' => null,
])

<article class="admin-indicador admin-indicador--{{ $tono }}" data-indicador="{{ $clave }}" data-valor="{{ $valor }}">
    <span class="admin-indicador__icono" aria-hidden="true">
        <x-icono :nombre="$icono" :tamano="22" />
    </span>
    <div class="admin-indicador__cuerpo">
        <p class="admin-indicador__etiqueta">{{ $etiqueta }}</p>
        <p class="admin-indicador__valor">{{ number_format((int) $valor, 0, ',', '.') }}</p>
        @if ($detalle)
            <p class="admin-indicador__detalle">{{ $detalle }}</p>
        @endif
    </div>
</article>
