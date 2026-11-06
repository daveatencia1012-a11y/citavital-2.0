@props(['activo', 'textoActivo' => 'Activo', 'textoInactivo' => 'Inactivo'])

<span class="admin-estado admin-estado--{{ $activo ? 'activo' : 'inactivo' }}">
    {{ $activo ? $textoActivo : $textoInactivo }}
</span>
