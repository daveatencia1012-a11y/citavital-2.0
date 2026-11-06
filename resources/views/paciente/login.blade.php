@extends('layouts.login')

@section('titulo', 'Portal del paciente')
@section('descripcion', 'Acceso demostrativo al portal del paciente de CitaVital.')

@section('contenido')
    <x-panel-login
        titulo="Portal del paciente"
        descripcion="Ingresa para gestionar tus citas médicas."
        saludo="¡BIENVENIDO A CITAVITAL!"
        mensaje="Consulta tus citas, conoce los horarios disponibles y realiza seguimiento a tus solicitudes."
        :destino="route('quienes-somos')"
        accion="CONOCE CITAVITAL"
    >
        @if (session('status'))
            <div class="login-estado login-estado--exito" role="status">{{ session('status') }}</div>
        @endif

        @php($errorCedula = $errors->first('document_number'))

        <form class="formulario login-form" data-formulario-acceso="paciente" action="{{ route('paciente.login.store') }}" method="post" novalidate>
            @csrf
            <div class="campo @if ($errorCedula) campo--error @endif" data-campo="cedula">
                <label class="campo__etiqueta" for="cedula">Número de cédula</label>
                <div class="campo__control">
                    <span class="campo__icono"><x-icono nombre="documento" :tamano="20" /></span>
                    <input class="campo__entrada" id="cedula" name="document_number" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9]{8}|[0-9]{10}" maxlength="10" placeholder="Ingresa tu número de cédula" aria-describedby="cedula-ayuda cedula-error" value="{{ old('document_number') }}" @if ($errorCedula) aria-invalid="true" @endif required data-cedula>
                </div>
                <p class="campo__ayuda" id="cedula-ayuda">Solo números, sin espacios. Exactamente 8 o 10 dígitos.</p>
                <p class="campo__error" id="cedula-error" role="alert" data-error-cedula @if (! $errorCedula) hidden @endif>{{ $errorCedula }}</p>
            </div>
            <button class="login-boton login-boton--primario" type="submit" data-login-enviar disabled>INGRESAR</button>
            <div class="login-estado" role="status" aria-live="polite" data-estado-acceso hidden><p></p></div>
        </form>
        @if ($demoHabilitado ?? false)
            <p class="login-nota"><x-icono nombre="escudo" :tamano="17" /><span><strong>Modo demostración local.</strong> Ingresa con una cédula ficticia registrada en los datos de prueba. El acceso real requerirá posteriormente una segunda verificación de identidad.</span></p>
        @else
            <p class="login-nota"><x-icono nombre="escudo" :tamano="17" /><span><strong>Modo demostración.</strong> El acceso con cédula está deshabilitado en este entorno. Se habilita únicamente en entorno local con pacientes ficticios; el uso real requerirá una segunda verificación de identidad.</span></p>
        @endif
        <p class="login-alterno">¿Eres parte del personal? <a href="{{ route('administrativo.login') }}">Acceso administrativo</a></p>
    </x-panel-login>
@endsection
