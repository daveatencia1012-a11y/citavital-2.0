@props(['nombre', 'tamano' => 24, 'grosor' => 1.8])

@php
    $trazos = [
        'cruz-medica' => '<circle cx="12" cy="12" r="10"/><path d="M10.5 6.5h3v4h4v3h-4v4h-3v-4h-4v-3h4Z" fill="currentColor" stroke="none"/>',
        'hospital' => '<path d="M7 22V3h10v19M7 10H2v12h20V10h-5M10 22v-5h4v5M11 6h2M12 5v2M10 11h.01M14 11h.01M10 14h.01M14 14h.01"/>',
        'usuario-administrativo' => '<circle cx="11" cy="6.5" r="4"/><path d="M14 13.5a8 8 0 0 0-3-.5c-4.5 0-8 3-8 7v1h11"/><circle cx="19" cy="18" r="4"/><path d="M19 16v2l1.2.8"/>',
        'institucion' => '<path d="M3 21h18"/><path d="M5 21V8l7-5 7 5v13"/><path d="M9 21v-4h6v4"/><path d="M12 7v4M10 9h4"/>',
        'calendario' => '<rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>',
        'admin' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><circle cx="12" cy="10" r="2.5"/><path d="M8.5 16a3.5 3.5 0 0 1 7 0"/>',
        'corazon' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/><path d="M3.5 12h4l2-3 3 6 2-3h5.5"/>',
        'escudo' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'reloj' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'telefono' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/>',
        'correo' => '<rect x="2" y="4" width="20" height="16" rx="3"/><path d="m3 7 9 6 9-6"/>',
        'ubicacion' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'flecha-derecha' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'flecha-izquierda' => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'check-circulo' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.3 2.3L15.5 9"/>',
        'buscar' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'documento' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/>',
        'estetoscopio' => '<path d="M6 3v6a5 5 0 0 0 10 0V3"/><path d="M4 3h4M14 3h4"/><path d="M11 14v2a5 5 0 0 0 10 0v-2"/><circle cx="21" cy="11" r="2"/>',
        'usuarios' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'usuario' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'ojo' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'lapiz' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'ojo-cerrado' => '<path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a17.8 17.8 0 0 1-3.2 4.1"/><path d="M6.6 6.6C3.6 8.4 2 12 2 12s3.5 7 10 7c1.5 0 2.8-.4 4-1"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'cerrar' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'chispas' => '<path d="m12 3 1.4 4.1L17.5 8.5l-4.1 1.4L12 14l-1.4-4.1-4.1-1.4 4.1-1.4L12 3Z"/><path d="m19 14 .7 2.3L22 17l-2.3.7L19 20l-.7-2.3L16 17l2.3-.7L19 14Z"/><path d="m5 15 .6 1.4L7 17l-1.4.6L5 19l-.6-1.4L3 17l1.4-.6L5 15Z"/>',
        'candado' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 14v3"/>',
        'historial' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
        'grafico' => '<path d="M3 3v18h18"/><path d="M7 16v-5M12 16V8M17 16v-9"/>',
        'mano-corazon' => '<path d="M11 14h2a2 2 0 0 0 0-4H9l-3 3"/><path d="M18 11V9a2 2 0 0 0-2-2h-3"/><path d="M2 12h3l3 3h6a2 2 0 0 0 0-4H9L7 9H2"/><path d="M19 8.5c.8-.5 1.5-1.3 1.5-2.2A2 2 0 0 0 17 5a2 2 0 0 0-3.5 1.3c0 .9.7 1.7 1.5 2.2l2 1.5 2-1.5Z"/>',
        'pastilla' => '<path d="m10.5 20.5-7-7a4.95 4.95 0 0 1 7-7l7 7a4.95 4.95 0 0 1-7 7Z"/><path d="m8.5 8.5 7 7"/>',
        'maletin' => '<rect x="2" y="7" width="20" height="14" rx="3"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M12 12v4M10 14h4"/>',
        'pulso' => '<path d="M3 12h4l2-6 4 12 2-6h6"/>',
        'pregunta' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.1 1.9c-.9.7-1.6 1.2-1.6 2.6"/><path d="M12 17h.01"/>',
        'alerta' => '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5"/><path d="M12 17h.01"/>',
        'edificio' => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/>',
        'brindar' => '<path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10Z"/>',
        'agenda' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18"/><path d="M8 15h2M14 15h2M8 18h2M14 18h2"/>',
        'basura' => '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
    ];
@endphp

<svg
    {{ $attributes->merge(['class' => 'icono']) }}
    width="{{ $tamano }}"
    height="{{ $tamano }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $grosor }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>
    {!! $trazos[$nombre] ?? '' !!}
</svg>
