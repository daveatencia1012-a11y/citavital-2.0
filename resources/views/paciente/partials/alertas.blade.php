@if (session('status'))
    <div class="paciente-alerta paciente-alerta--exito" role="status">
        <x-icono nombre="check-circulo" :tamano="20" />
        <div>
            <strong>Operación realizada</strong>
            <p>{{ session('status') }}</p>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="paciente-alerta paciente-alerta--error" role="alert">
        <x-icono nombre="alerta" :tamano="20" />
        <div>
            <strong>Revisa la información</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
