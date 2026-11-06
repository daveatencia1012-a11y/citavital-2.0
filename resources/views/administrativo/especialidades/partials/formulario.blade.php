@php
    $editando = $especialidad !== null;
@endphp

<form
    class="admin-formulario"
    method="POST"
    action="{{ $editando ? route('administrativo.especialidades.update', $especialidad) : route('administrativo.especialidades.store') }}"
>
    @csrf
    @if ($editando)
        @method('PUT')
    @endif

    <section class="admin-panel" aria-labelledby="titulo-datos-especialidad">
        <header class="admin-panel__cabecera">
            <div>
                <p class="admin-panel__sobre">Información clínica</p>
                <h3 id="titulo-datos-especialidad">Datos de la especialidad</h3>
                <p>El nombre es obligatorio y no puede repetirse, incluso si cambia el uso de mayúsculas o espacios.</p>
            </div>
            @if ($editando)
                <x-estado-registro :activo="$especialidad->is_active" />
            @endif
        </header>

        <div class="admin-formulario__campos">
            <div class="campo @error('name') campo--error @enderror">
                <label class="campo__etiqueta" for="name">Nombre de la especialidad</label>
                <div class="campo__control">
                    <x-icono nombre="institucion" :tamano="18" class="campo__icono" />
                    <input
                        class="campo__entrada"
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $especialidad?->name) }}"
                        maxlength="100"
                        autocomplete="off"
                        required
                        @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                    >
                </div>
                @error('name')
                    <p class="campo__error" id="name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo campo--completo @error('description') campo--error @enderror">
                <label class="campo__etiqueta" for="description">Descripción</label>
                <div class="campo__control campo__control--area">
                    <textarea
                        class="campo__entrada campo__entrada--area"
                        id="description"
                        name="description"
                        rows="5"
                        maxlength="2000"
                        placeholder="Describe brevemente el alcance de la especialidad."
                        @error('description') aria-invalid="true" aria-describedby="description-error" @enderror
                    >{{ old('description', $especialidad?->description) }}</textarea>
                </div>
                <p class="campo__ayuda">Opcional. Máximo 2000 caracteres.</p>
                @error('description')
                    <p class="campo__error" id="description-error">{{ $message }}</p>
                @enderror
            </div>

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
                            <strong>Especialidad activa</strong>
                            <small>Podrá asignarse a médicos y utilizarse en nuevas reservas.</small>
                        </span>
                    </label>
                    <p class="campo__ayuda">Si la desactivas después, las citas existentes se conservarán.</p>
                </div>
            @else
                <div class="campo campo--completo">
                    <div class="admin-nota-interna">
                        <x-icono nombre="escudo" :tamano="18" />
                        <p>El estado se cambia desde el listado con las acciones de activar o desactivar. Las citas existentes no se cancelan.</p>
                    </div>
                </div>
            @endif
        </div>

        <footer class="admin-formulario__acciones">
            <p class="admin-formulario__nota">
                <x-icono nombre="escudo" :tamano="16" />
                La información se valida nuevamente en el servidor.
            </p>
            <div class="admin-formulario__botones">
                <a class="boton boton--secundario" href="{{ route('administrativo.especialidades.index') }}">Cancelar</a>
                <button class="boton boton--primario" type="submit">
                    <x-icono nombre="check-circulo" :tamano="18" />
                    {{ $editando ? 'Guardar cambios' : 'Registrar especialidad' }}
                </button>
            </div>
        </footer>
    </section>
</form>
