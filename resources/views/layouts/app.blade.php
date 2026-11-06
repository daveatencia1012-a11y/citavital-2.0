<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="@yield('color-tema', '#12345B')">
    <meta name="description" content="@yield('descripcion', 'CitaVital: plataforma académica de gestión de citas médicas.')">
    <title>@yield('titulo', 'CitaVital')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @stack('precargas')
    @vite(['resources/css/app.css', 'resources/css/componentes.css', 'resources/js/app.js'])
    @stack('estilos')
</head>
<body class="@yield('clase-cuerpo')">
    <a class="salto-contenido" href="#contenido">Saltar al contenido principal</a>
    @hasSection('encabezado')
        @yield('encabezado')
    @else
        @include('partials.encabezado')
    @endif
    <main class="@yield('clase-contenido', 'contenido-sitio')" id="contenido" tabindex="-1">
        @yield('contenido')
    </main>
    @hasSection('pie')
        @yield('pie')
    @else
        @include('partials.pie')
    @endif
    @stack('scripts')
</body>
</html>
