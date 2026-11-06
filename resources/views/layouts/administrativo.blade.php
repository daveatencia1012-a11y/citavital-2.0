<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#12345B">
    <meta name="description" content="@yield('descripcion', 'Panel administrativo de CitaVital.')">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('titulo', 'Panel administrativo') | CitaVital</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite([
        'resources/css/app.css',
        'resources/css/componentes.css',
        'resources/css/administrativo.css',
        'resources/js/administrativo.js',
    ])
</head>
<body class="admin-cuerpo">
    <a class="salto-contenido" href="#contenido">Saltar al contenido principal</a>

    @include('administrativo.partials.sidebar')

    <div class="admin-scrim" data-admin-scrim hidden></div>

    <div class="admin-shell">
        <header class="admin-cabecera">
            <div class="admin-cabecera__principal">
                <button class="admin-menu-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Abrir menú de navegación" data-admin-menu-toggle>
                    <x-icono nombre="menu" :tamano="22" />
                </button>

                <div class="admin-cabecera__modulo">
                    <p class="admin-cabecera__sistema">CitaVital</p>
                    <h1>@yield('titulo', 'Dashboard')</h1>
                </div>
            </div>

            <div class="admin-cabecera__lateral">
                <p class="admin-cabecera__fecha">
                    <x-icono nombre="calendario" :tamano="17" />
                    <span>{{ $currentDate }}</span>
                    <small>Hora de Bogotá</small>
                </p>

                <div class="admin-cabecera__cuenta">
                    <span class="admin-avatar" aria-hidden="true">
                        <x-icono nombre="usuario-administrativo" :tamano="20" />
                    </span>
                    <span class="admin-cabecera__usuario">
                        <strong>{{ $administrador->name }}</strong>
                        <small>{{ $administrador->email }}</small>
                    </span>
                </div>

                <span class="admin-cabecera__sesion">
                    <span class="admin-punto" aria-hidden="true"></span>
                    Sesión activa
                </span>

                <form method="post" action="{{ route('administrativo.logout') }}">
                    @csrf
                    <button class="admin-salir" type="submit">
                        <x-icono nombre="cerrar" :tamano="17" />
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-principal" id="contenido" tabindex="-1">
            @yield('contenido')
        </main>

        <footer class="admin-pie">
            <div class="admin-pie__interior">
                <p>CitaVital &copy; {{ date('Y') }} · Panel administrativo</p>
                <p><a href="{{ route('politica-privacidad') }}">Privacidad</a></p>
            </div>
        </footer>
    </div>
</body>
</html>
