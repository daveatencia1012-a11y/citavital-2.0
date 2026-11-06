@php
    $editando = $medico !== null;
    $seleccionadas = collect(old('specialties', $medico?->specialties->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<form
    class="admin-formulario"
    method="POST"
    action="{{ $editando ? route('administrativo.medicos.update', $medico) : route('administrativo.medicos.store') }}"
>
    @csrf
    @if ($editando)
        @method('PUT')
    @endif

    <section class="admin-panel" aria-labelledby="titulo-datos-medico">
        <header class="admin-panel__cabecera">
            <div>
                <p class="admin-panel__sobre">Información profesional</p>
                <h3 id="titulo-datos-medico">Datos del médico</h3>
                <p>El código interno es único. Asigna una o varias especialidades existentes.</p>
            </div>
            @if ($editando)
                <x-estado-registro :activo="$medico->is_active" />
            @endif
        </header>

        <div class="admin-formulario__campos">
            <div class="campo @error('internal_code') campo--error @enderror">
                <label class="campo__etiqueta" for="internal_code">Código interno</label>
                <div class="campo__control">
                    <x-icono nombre="documento" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="internal_code"
                        name="internal_code"
                        type="text"
                        value="{{ old('internal_code', $medico?->internal_code) }}"
                        maxlength="40"
                        autocomplete="off"
                        spellcheck="false"
                        required
                        @error('internal_code') aria-invalid="true" aria-describedby="internal_code-error" @enderror
                    >
                </div>
                <p class="campo__ayuda">Ejemplo: MED-006. No puede repetirse.</p>
                @error('internal_code')
                    <p class="campo__error" id="internal_code-error">{{ $message }}</p>
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
                        value="{{ old('first_name', $medico?->first_name) }}"
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
                        value="{{ old('last_name', $medico?->last_name) }}"
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

            <fieldset class="campo campo--completo @error('specialties') campo--error @enderror">
                <legend class="campo__etiqueta">Especialidades</legend>

                @if ($especialidades->isEmpty())
                    <div class="admin-nota-interna admin-nota-interna--advertencia">
                        <x-icono nombre="alerta" :tamano="18" />
                        <p>No hay especialidades registradas. <a href="{{ route('administrativo.especialidades.create') }}">Registra una especialidad</a> antes de crear un médico.</p>
                    </div>
                @else
                    <div class="admin-check-grid">
                        @foreach ($especialidades as $especialidad)
                            <label class="admin-check admin-check--compacto">
                                <input
                                    type="checkbox"
                                    name="specialties[]"
                                    value="{{ $especialidad->id }}"
                                    @checked(in_array((int) $especialidad->id, $seleccionadas, true))
                                >
                                <span>
                                    <strong>{{ $especialidad->name }}</strong>
                                    <small>{{ $especialidad->is_active ? 'Disponible para nuevas reservas' : 'Inactiva: no admite nuevas reservas' }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="campo__ayuda">Selecciona al menos una especialidad. Las especialidades inactivas se conservan para el historial.</p>
                @endif

                @error('specialties')
                    <p class="campo__error" id="specialties-error">{{ $message }}</p>
                @enderror
            </fieldset>

            @if (! $editando)
                <div class="campo campo--completo">
                    <label class="admin-check" for="is_active">
                        <input type="hidden" name="is_active" value="0">
                        <input
                            id="is_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            @checked((bool) old('is_active', true))
                        >
                        <span>
                            <strong>Médico activo</strong>
                            <small>Podrá recibir nuevas reservas y aparecer en el selector de horarios.</small>
                        </span>
                    </label>
                </div>
            @else
                <div class="campo campo--completo">
                    <div class="admin-nota-interna">
                        <x-icono nombre="escudo" :tamano="18" />
                        <p>El estado se cambia desde el listado. Si el médico tiene citas futuras, se conservarán al desactivarlo.</p>
                    </div>
                </div>
            @endif
        </div>

        <footer class="admin-formulario__acciones">
            <p class="admin-formulario__nota">
                <x-icono nombre="escudo" :tamano="16" />
                No se puede quitar una especialidad asociada a citas futuras.
            </p>
            <div class="admin-formulario__botones">
                <a class="boton boton--secundario" href="{{ route('administrativo.medicos.index') }}">Cancelar</a>
                <button class="boton boton--primario" type="submit" @disabled($especialidades->isEmpty())>
                    <x-icono nombre="check-circulo" :tamano="18" />
                    {{ $editando ? 'Guardar cambios' : 'Registrar médico' }}
                </button>
            </div>
        </footer>
    </section>
</form>
