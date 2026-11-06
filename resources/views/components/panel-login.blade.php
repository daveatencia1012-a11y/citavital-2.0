@props([
    'titulo',
    'descripcion',
    'saludo',
    'mensaje',
    'destino',
    'accion',
    'avisoSinJs' => 'Activa JavaScript para revisar las validaciones de esta interfaz demostrativa. El inicio de sesión aún no está habilitado.',
])

<div class="login-paneles">
    <section class="login-formulario" aria-labelledby="titulo-login">
        <div class="login-formulario__interior">
            <h1 id="titulo-login">{{ $titulo }}</h1>
            <p class="login-formulario__descripcion">{{ $descripcion }}</p>
            {{ $slot }}
            <noscript><p class="aviso aviso--info">{{ $avisoSinJs }}</p></noscript>
            <a class="login-volver" href="{{ route('inicio') }}"><x-icono nombre="flecha-izquierda" :tamano="16" />Volver al inicio</a>
        </div>
    </section>

    <aside class="login-bienvenida" aria-labelledby="titulo-bienvenida">
        <div class="login-bienvenida__formas" aria-hidden="true">
            <span class="login-forma login-forma--circulo"></span>
            <span class="login-forma login-forma--arco"></span>
            <span class="login-forma login-forma--orbita"></span>
        </div>
        <div class="login-bienvenida__contenido">
            <x-icono nombre="cruz-medica" :tamano="46" :grosor="1.3" />
            <h2 id="titulo-bienvenida">{{ $saludo }}</h2>
            <p>{{ $mensaje }}</p>
            <a class="login-boton login-boton--secundario" href="{{ $destino }}">{{ $accion }}</a>
        </div>
    </aside>
</div>
