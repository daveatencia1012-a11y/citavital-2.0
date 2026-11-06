@if (session('status'))
    <div class="admin-alerta admin-alerta--exito" role="status">
        <x-icono nombre="check-circulo" :tamano="20" />
        <div>
            <strong>Operación realizada</strong>
            <p>{{ session('status') }}</p>
        </div>
    </div>
@endif

@if (session('warning'))
    <div class="admin-alerta admin-alerta--advertencia" role="status">
        <x-icono nombre="alerta" :tamano="20" />
        <div>
            <strong>Ten en cuenta</strong>
            <p>{{ session('warning') }}</p>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="admin-alerta admin-alerta--error" role="alert">
        <x-icono nombre="alerta" :tamano="20" />
        <div>
            <strong>Revisa la información ingresada</strong>
            <p>Corrige los siguientes campos antes de continuar:</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
