@extends('layouts.login')

@section('titulo', 'Acceso administrativo')
@section('descripcion', 'Acceso para el personal administrativo autorizado de CitaVital.')

@section('contenido')
    <x-panel-login
        titulo="Acceso administrativo"
        descripcion="Ingresa tus credenciales para continuar."
        saludo="¡BIENVENIDO!"
        mensaje="Administra pacientes, médicos, horarios y citas desde un solo lugar."
        :destino="route('inicio')"
        accion="IR AL INICIO"
        aviso-sin-js="Activa JavaScript para habilitar el formulario de acceso administrativo."
    >
        @if (session('status'))
            <div class="login-estado login-estado--exito" role="status">{{ session('status') }}</div>
        @endif

        <form class="formulario login-form" data-formulario-acceso="administrativo" action="{{ route('administrativo.login.store') }}" method="post" novalidate>
            @csrf
            <div class="campo @error('email') campo--error @enderror" data-campo="correo">
                <label class="campo__etiqueta" for="correo">Correo electrónico</label>
                <div class="campo__control">
                    <span class="campo__icono"><x-icono nombre="correo" :tamano="20" /></span>
                    <input class="campo__entrada" id="correo" name="email" type="email" autocomplete="username" placeholder="Ingresa tu correo electrónico" value="{{ old('email') }}" aria-describedby="correo-error" @error('email') aria-invalid="true" @enderror required data-correo-admin>
                </div>
                <p class="campo__error" id="correo-error" role="alert" data-error-correo @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</p>
            </div>
            <div class="campo @error('password') campo--error @enderror" data-campo="password">
                <label class="campo__etiqueta" for="password">Contraseña</label>
                <div class="campo__control campo__control--accion">
                    <span class="campo__icono"><x-icono nombre="candado" :tamano="20" /></span>
                    <input class="campo__entrada" id="password" name="password" type="password" autocomplete="current-password" placeholder="Ingresa tu contraseña" aria-describedby="password-error" @error('password') aria-invalid="true" @enderror required data-password>
                    <button class="campo__boton" type="button" aria-controls="password" aria-pressed="false" aria-label="Mostrar contraseña" data-alternar-password>
                        <x-icono nombre="ojo" :tamano="20" class="icono-ojo" />
                        <x-icono nombre="ojo-cerrado" :tamano="20" class="icono-ojo-cerrado" />
                    </button>
                </div>
                <p class="campo__error" id="password-error" role="alert" data-error-password @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</p>
            </div>
            <button class="login-boton login-boton--primario" type="submit" data-login-enviar disabled>INICIAR SESIÓN</button>
        </form>
        <p class="login-nota"><x-icono nombre="candado" :tamano="17" /><span>Acceso exclusivo para personal administrativo autorizado. La sesión se protege con validación de credenciales, CSRF y límite de intentos.</span></p>
        <p class="login-alterno">¿Buscas tus citas? <a href="{{ route('paciente.login') }}">Portal del paciente</a></p>
    </x-panel-login>
@endsection
