<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#12345B">
    <meta name="description" content="@yield('descripcion', 'Portal del paciente de CitaVital.')">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('titulo', 'Portal del paciente') | CitaVital</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite([
        'resources/css/app.css',
        'resources/css/componentes.css',
        'resources/css/paciente.css',
        'resources/js/paciente.js',
    ])
</head>
<body class="paciente-cuerpo">
    <a class="salto-contenido" href="#contenido">Saltar al contenido principal</a>

    @php($paciente = auth('patient')->user())

    <header class="paciente-cabecera">
        <div class="paciente-cabecera__interior">
            <a class="paciente-marca" href="{{ route('paciente.dashboard') }}" aria-label="CitaVital, ir al resumen del paciente">
                <span class="paciente-marca__logo"><x-logo-citavital :tamano="38" /></span>
                <span class="paciente-marca__texto">
                    <strong>CitaVital</strong>
                    <small>Portal del paciente</small>
                </span>
            </a>

            <button
                class="paciente-menu"
                type="button"
                aria-controls="paciente-nav"
                aria-expanded="false"
                aria-label="Abrir menú de navegación"
                data-paciente-menu-toggle
            >
                <x-icono nombre="menu" :tamano="22" />
            </button>

            <nav class="paciente-nav" id="paciente-nav" aria-label="Navegación del portal del paciente" data-paciente-nav>
                <a class="paciente-nav__enlace {{ request()->routeIs('paciente.dashboard') ? 'paciente-nav__enlace--activo' : '' }}" href="{{ route('paciente.dashboard') }}" @if (request()->routeIs('paciente.dashboard')) aria-current="page" @endif>
                    <x-icono nombre="grafico" :tamano="17" />
                    <span>Resumen</span>
                </a>
                <a class="paciente-nav__enlace {{ request()->routeIs('paciente.citas.solicitar') ? 'paciente-nav__enlace--activo' : '' }}" href="{{ route('paciente.citas.solicitar') }}" @if (request()->routeIs('paciente.citas.solicitar')) aria-current="page" @endif>
                    <x-icono nombre="calendario" :tamano="17" />
                    <span>Solicitar cita</span>
                </a>
                <a class="paciente-nav__enlace {{ request()->routeIs('paciente.citas.index') ? 'paciente-nav__enlace--activo' : '' }}" href="{{ route('paciente.citas.index') }}" @if (request()->routeIs('paciente.citas.index')) aria-current="page" @endif>
                    <x-icono nombre="agenda" :tamano="17" />
                    <span>Próximas citas</span>
                </a>
                <a class="paciente-nav__enlace {{ request()->routeIs('paciente.citas.historial') ? 'paciente-nav__enlace--activo' : '' }}" href="{{ route('paciente.citas.historial') }}" @if (request()->routeIs('paciente.citas.historial')) aria-current="page" @endif>
                    <x-icono nombre="historial" :tamano="17" />
                    <span>Historial</span>
                </a>
            </nav>

            <div class="paciente-cuenta">
                <span class="paciente-cuenta__avatar" aria-hidden="true">
                    <x-icono nombre="usuario" :tamano="19" />
                </span>
                <span class="paciente-cuenta__datos">
                    <strong>{{ $paciente?->full_name }}</strong>
                    <small>Cédula {{ $paciente?->document_number }}</small>
                </span>
                <form method="post" action="{{ route('paciente.logout') }}">
                    @csrf
                    <button class="paciente-salir" type="submit">
                        <x-icono nombre="cerrar" :tamano="17" />
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="paciente-principal" id="contenido" tabindex="-1">
        @include('paciente.partials.alertas')
        @yield('contenido')
    </main>

    <footer class="paciente-pie">
        <div class="paciente-pie__interior">
            <p>CitaVital &copy; {{ date('Y') }} · Portal del paciente</p>
            <p>Hora de Bogotá · <a href="{{ route('politica-privacidad') }}">Privacidad</a></p>
        </div>
    </footer>
</body>
</html>
