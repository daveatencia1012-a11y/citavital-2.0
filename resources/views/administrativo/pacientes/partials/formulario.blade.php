@php
    $editando = $paciente !== null;
@endphp

<form
    class="admin-formulario"
    method="POST"
    action="{{ $editando ? route('administrativo.pacientes.update', $paciente) : route('administrativo.pacientes.store') }}"
>
    @csrf
    @if ($editando)
        @method('PUT')
    @endif

    <section class="admin-panel" aria-labelledby="titulo-datos-paciente">
        <header class="admin-panel__cabecera">
            <div>
                <p class="admin-panel__sobre">Información personal</p>
                <h3 id="titulo-datos-paciente">Datos del paciente</h3>
                <p>Todos los campos son obligatorios. La edad se calcula automáticamente desde la fecha de nacimiento.</p>
            </div>
        </header>

        <div class="admin-formulario__campos">
            <div class="campo @error('document_number') campo--error @enderror">
                <label class="campo__etiqueta" for="document_number">Número de cédula</label>
                <div class="campo__control">
                    <x-icono nombre="documento" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="document_number"
                        name="document_number"
                        type="text"
                        value="{{ old('document_number', $paciente?->document_number) }}"
                        maxlength="10"
                        inputmode="numeric"
                        pattern="[0-9]{8}|[0-9]{10}"
                        autocomplete="off"
                        spellcheck="false"
                        data-solo-digitos
                        data-max-digitos="10"
                        required
                        @error('document_number') aria-invalid="true" aria-describedby="document_number-error" @enderror
                    >
                </div>
                <p class="campo__ayuda">Debe contener exactamente 8 o 10 dígitos numéricos.</p>
                @error('document_number')
                    <p class="campo__error" id="document_number-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo @error('birth_date') campo--error @enderror">
                <label class="campo__etiqueta" for="birth_date">Fecha de nacimiento</label>
                <div class="campo__control">
                    <x-icono nombre="calendario" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="birth_date"
                        name="birth_date"
                        type="date"
                        value="{{ old('birth_date', $paciente?->birth_date?->format('Y-m-d')) }}"
                        max="{{ $maxBirthDate }}"
                        autocomplete="bday"
                        data-fecha-nacimiento
                        required
                        @error('birth_date') aria-invalid="true" aria-describedby="birth_date-error" @enderror
                    >
                </div>
                <p class="campo__ayuda" data-edad-paciente aria-live="polite">La edad se calculará al elegir la fecha.</p>
                @error('birth_date')
                    <p class="campo__error" id="birth_date-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo @error('first_name') campo--error @enderror">
                <label class="campo__etiqueta" for="first_name">Nombres</label>
                <div class="campo__control">
                    <x-icono nombre="usuario" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="first_name"
                        name="first_name"
                        type="text"
                        value="{{ old('first_name', $paciente?->first_name) }}"
                        maxlength="100"
                        autocomplete="given-name"
                        required
                        @error('first_name') aria-invalid="true" aria-describedby="first_name-error" @enderror
                    >
                </div>
                @error('first_name')
                    <p class="campo__error" id="first_name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo @error('last_name') campo--error @enderror">
                <label class="campo__etiqueta" for="last_name">Apellidos</label>
                <div class="campo__control">
                    <x-icono nombre="usuario" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="last_name"
                        name="last_name"
                        type="text"
                        value="{{ old('last_name', $paciente?->last_name) }}"
                        maxlength="100"
                        autocomplete="family-name"
                        required
                        @error('last_name') aria-invalid="true" aria-describedby="last_name-error" @enderror
                    >
                </div>
                @error('last_name')
                    <p class="campo__error" id="last_name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo @error('insurance_type') campo--error @enderror">
                <label class="campo__etiqueta" for="insurance_type">Tipo de seguro</label>
                <div class="campo__control">
                    <x-icono nombre="escudo" :tamano="18" class="campo__icono" />
                    <select
                        class="campo__entrada"
                        id="insurance_type"
                        name="insurance_type"
                        required
                        @error('insurance_type') aria-invalid="true" aria-describedby="insurance_type-error" @enderror
                    >
                        <option value="">Selecciona una opción</option>
                        @foreach ($tiposSeguro as $tipo)
                            <option value="{{ $tipo->value }}" @selected(old('insurance_type', $paciente?->insurance_type) === $tipo->value)>
                                {{ $tipo->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('insurance_type')
                    <p class="campo__error" id="insurance_type-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo @error('primary_phone') campo--error @enderror">
                <label class="campo__etiqueta" for="primary_phone">Teléfono principal</label>
                <div class="campo__control">
                    <x-icono nombre="telefono" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="primary_phone"
                        name="primary_phone"
                        type="tel"
                        value="{{ old('primary_phone', $paciente?->primary_phone) }}"
                        maxlength="10"
                        inputmode="numeric"
                        pattern="3[0-9]{9}"
                        autocomplete="tel"
                        data-solo-digitos
                        data-max-digitos="10"
                        required
                        @error('primary_phone') aria-invalid="true" aria-describedby="primary_phone-error" @enderror
                    >
                </div>
                <p class="campo__ayuda">10 dígitos numéricos. Debe comenzar por 3.</p>
                @error('primary_phone')
                    <p class="campo__error" id="primary_phone-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo @error('secondary_phone') campo--error @enderror">
                <label class="campo__etiqueta" for="secondary_phone">Teléfono secundario</label>
                <div class="campo__control">
                    <x-icono nombre="telefono" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="secondary_phone"
                        name="secondary_phone"
                        type="tel"
                        value="{{ old('secondary_phone', $paciente?->secondary_phone) }}"
                        maxlength="10"
                        inputmode="numeric"
                        pattern="[0-9]{7,10}"
                        autocomplete="tel-national"
                        data-solo-digitos
                        data-max-digitos="10"
                        required
                        @error('secondary_phone') aria-invalid="true" aria-describedby="secondary_phone-error" @enderror
                    >
                </div>
                <p class="campo__ayuda">Entre 7 y 10 dígitos numéricos.</p>
                @error('secondary_phone')
                    <p class="campo__error" id="secondary_phone-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo campo--completo @error('address') campo--error @enderror">
                <label class="campo__etiqueta" for="address">Dirección</label>
                <div class="campo__control">
                    <x-icono nombre="ubicacion" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="address"
                        name="address"
                        type="text"
                        value="{{ old('address', $paciente?->address) }}"
                        maxlength="255"
                        autocomplete="street-address"
                        required
                        @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
                    >
                </div>
                @error('address')
                    <p class="campo__error" id="address-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <footer class="admin-formulario__acciones">
            <p class="admin-formulario__nota">
                <x-icono nombre="escudo" :tamano="16" />
                La información se valida nuevamente en el servidor.
            </p>
            <div class="admin-formulario__botones">
                <a class="boton boton--secundario" href="{{ route('administrativo.pacientes.index') }}">Cancelar</a>
                <button class="boton boton--primario" type="submit">
                    <x-icono nombre="check-circulo" :tamano="18" />
                    {{ $editando ? 'Guardar cambios' : 'Registrar paciente' }}
                </button>
            </div>
        </footer>
    </section>
</form>
