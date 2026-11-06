@if ($paginator->hasPages())
    <nav class="paciente-paginacion" aria-label="Paginación del historial de citas">
        <p class="paciente-paginacion__resumen">
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </p>

        <div class="paciente-paginacion__enlaces">
            @if ($paginator->onFirstPage())
                <span class="paciente-paginacion__enlace paciente-paginacion__enlace--deshabilitado" aria-disabled="true">
                    <x-icono nombre="flecha-izquierda" :tamano="15" />
                    <span>Anterior</span>
                </span>
            @else
                <a class="paciente-paginacion__enlace" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <x-icono nombre="flecha-izquierda" :tamano="15" />
                    <span>Anterior</span>
                </a>
            @endif

            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $pagina => $url)
                @if ($pagina === $paginator->currentPage())
                    <span class="paciente-paginacion__pagina paciente-paginacion__pagina--activa" aria-current="page">{{ $pagina }}</span>
                @else
                    <a class="paciente-paginacion__pagina" href="{{ $url }}" aria-label="Ir a la página {{ $pagina }}">{{ $pagina }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="paciente-paginacion__enlace" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    <span>Siguiente</span>
                    <x-icono nombre="flecha-derecha" :tamano="15" />
                </a>
            @else
                <span class="paciente-paginacion__enlace paciente-paginacion__enlace--deshabilitado" aria-disabled="true">
                    <span>Siguiente</span>
                    <x-icono nombre="flecha-derecha" :tamano="15" />
                </span>
            @endif
        </div>
    </nav>
@endif
