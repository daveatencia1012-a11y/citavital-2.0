@php
    $rutaActual = request()->route()?->getName();
    [$seccion, $contexto] = match ($rutaActual) {
        'quienes-somos' => ['Información institucional', 'Conoce nuestra propuesta y los servicios previstos.'],
        'paciente.login' => ['Acceso de pacientes', 'Tu espacio para la gestión de citas médicas.'],
        'administrativo.login' => ['Acceso administrativo', 'Espacio de trabajo para personal autorizado.'],
        'politica-privacidad' => ['Privacidad', 'Información sobre el uso del prototipo académico.'],
        default => ['Inicio', 'Bienvenido a tu espacio de atención.'],
    };
    $enlaces = [
        ['inicio', 'Inicio', 'edificio'],
        ['paciente.login', 'Acceso a mis citas', 'calendario'],
        ['quienes-somos', 'Información', 'institucion'],
        ['administrativo.login', 'Administración', 'usuario-administrativo'],
    ];
@endphp

<aside class="barra-lateral" aria-label="Navegación de CitaVital">
    <div class="barra-lateral__marca">
        <a class="marca" href="{{ route('inicio') }}" aria-label="CitaVital, ir al inicio">
            <x-logo-citavital :tamano="36" />
            <span class="marca__texto">CitaVital</span>
        </a>
        <button class="boton-menu" type="button" aria-expanded="false" aria-controls="nav-principal" aria-label="Abrir menú de navegación" data-menu-toggle hidden>
            <x-icono nombre="menu" :tamano="22" />
        </button>
    </div>
    <nav class="nav-principal" id="nav-principal" aria-label="Navegación principal" data-nav data-abierto="false">
        <p class="nav-principal__titulo">Tu espacio de salud</p>
        @foreach ($enlaces as [$ruta, $texto, $icono])
            <a class="nav-principal__enlace" href="{{ route($ruta) }}" @if ($rutaActual === $ruta) aria-current="page" @endif>
                <x-icono :nombre="$icono" :tamano="20" />
                <span>{{ $texto }}</span>
            </a>
        @endforeach
        <div class="nav-principal__ayuda">
            <p class="nav-principal__titulo">Te orientamos</p>
            <a class="nav-principal__enlace" href="{{ route('quienes-somos') }}#preguntas"><x-icono nombre="pregunta" :tamano="20" />Ayuda y preguntas</a>
            <a class="nav-principal__enlace" href="#contacto"><x-icono nombre="correo" :tamano="20" />Contacto</a>
            <a class="nav-principal__enlace" href="{{ route('politica-privacidad') }}" @if ($rutaActual === 'politica-privacidad') aria-current="page" @endif><x-icono nombre="escudo" :tamano="20" />Privacidad</a>
            <p class="barra-lateral__nota">Prototipo académico<br>Información demostrativa</p>
        </div>
    </nav>
</aside>

<header class="encabezado-sitio">
    <div>
        <p class="encabezado-sitio__titulo">{{ $seccion }}</p>
        <p class="encabezado-sitio__contexto">{{ $contexto }}</p>
    </div>
    <a class="cuenta-enlace" href="{{ route('paciente.login') }}" aria-label="Acceso de pacientes"><x-icono nombre="usuario" :tamano="20" /><span>Acceso de pacientes</span></a>
</header>
