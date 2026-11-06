<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#164E63">
    <meta name="description" content="@yield('descripcion')">
    <title>@yield('titulo') | CitaVital</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/css/componentes.css', 'resources/css/login.css', 'resources/js/login.js'])
</head>
<body class="pagina-login">
    <a class="salto-contenido" href="#contenido">Saltar al formulario</a>
    <header class="login-cabecera">
        <a class="login-marca" href="{{ route('inicio') }}" aria-label="CitaVital, ir al inicio">
            <x-icono nombre="cruz-medica" :tamano="34" />
            <span>CitaVital</span>
        </a>
        <span class="login-cabecera__descripcion">Gestión de citas médicas</span>
    </header>
    <main class="login-principal" id="contenido" tabindex="-1">
        @yield('contenido')
    </main>
    <footer class="login-pie">
        <p>CitaVital &copy; {{ date('Y') }} · Prototipo académico</p>
        <a href="{{ route('politica-privacidad') }}">Privacidad demostrativa</a>
    </footer>
</body>
</html>
