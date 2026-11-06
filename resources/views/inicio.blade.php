@extends('layouts.app')

@section('titulo', 'CitaVital | Gestión de citas médicas')
@section('descripcion', 'Solicita, consulta y realiza seguimiento a tus citas de manera rápida y organizada.')
@section('color-tema', '#163F52')
@section('clase-cuerpo', 'pagina-inicio')
@section('clase-contenido', 'inicio-principal')

@push('precargas')
    <link rel="preload" as="image" href="{{ asset('images/fondo-medico.webp') }}">
@endpush

@push('estilos')
    @vite('resources/css/inicio.css')
@endpush

@push('scripts')
    @vite('resources/js/inicio.js')
@endpush

@section('encabezado')
    <header class="inicio-encabezado">
        <div class="inicio-contenedor inicio-encabezado__interior">
            <a class="inicio-marca" href="{{ route('inicio') }}" aria-label="CitaVital, inicio">
                <x-icono nombre="cruz-medica" :tamano="40" :grosor="1.7" />
                <span>CitaVital</span>
            </a>
            <button class="inicio-menu" type="button" aria-label="Abrir menú de navegación" aria-controls="navegacion-inicio" aria-expanded="false" data-inicio-menu hidden>
                <x-icono nombre="menu" :tamano="25" />
            </button>
            <nav class="inicio-navegacion" id="navegacion-inicio" aria-label="Navegación principal">
                <a href="{{ route('inicio') }}" aria-current="page">Inicio</a>
                <a href="{{ route('quienes-somos') }}">Información</a>
                <a href="{{ route('quienes-somos') }}#contacto">Contacto</a>
            </nav>
        </div>
    </header>
@endsection

@section('contenido')
    <section class="inicio-heroe" aria-labelledby="titulo-principal">
        <div class="inicio-contenedor inicio-heroe__contenido">
            <h1 id="titulo-principal">CitaVital</h1>
            <p class="inicio-heroe__subtitulo">Tu plataforma de gestión de citas médicas</p>
            <p class="inicio-heroe__descripcion">Solicita, consulta y realiza seguimiento a tus citas de manera rápida y organizada.</p>
            <span class="inicio-heroe__linea" aria-hidden="true"></span>
        </div>
    </section>

    <nav class="inicio-accesos inicio-contenedor" aria-label="Accesos principales">
        <a class="inicio-acceso" href="{{ route('quienes-somos') }}">
            <div class="inicio-acceso__contenido">
                <x-icono nombre="hospital" :tamano="50" :grosor="1.5" />
                <h2>¿Quiénes somos?</h2>
                <p>Conoce nuestra institución y servicios.</p>
            </div>
        </a>
        <a class="inicio-acceso" href="{{ route('paciente.login') }}">
            <div class="inicio-acceso__contenido">
                <x-icono nombre="calendario" :tamano="50" :grosor="1.5" />
                <h2>Citas</h2>
                <p>Gestiona tus citas médicas.</p>
            </div>
        </a>
        <a class="inicio-acceso" href="{{ route('administrativo.login') }}">
            <div class="inicio-acceso__contenido">
                <x-icono nombre="usuario-administrativo" :tamano="50" :grosor="1.5" />
                <h2>Administrativo</h2>
                <p>Ingresa al panel de gestión.</p>
            </div>
        </a>
    </nav>
@endsection

@section('pie')
    <footer class="inicio-pie">
        <p>CitaVital &copy; {{ date('Y') }}</p>
        <p>Proyecto académico de gestión de citas médicas.</p>
    </footer>
@endsection
