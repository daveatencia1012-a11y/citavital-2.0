@php
    $editando = $disponibilidad !== null;
    $weekdayActual = (int) old('weekday', $disponibilidad?->weekday ?? 1);
    $horaInicio = old('start_time', $disponibilidad ? substr((string) $disponibilidad->start_time, 0, 5) : '08:00');
    $horaFin = old('end_time', $disponibilidad ? substr((string) $disponibilidad->end_time, 0, 5) : '12:00');
    $duracion = old('appointment_duration', $disponibilidad?->appointment_duration ?? 30);
    $activo = (bool) old('is_active', $disponibilidad?->is_active ?? true);
@endphp

<div class="admin-horario-form__grid">
    <div class="campo @error('weekday') campo--error @enderror">
        <label class="campo__etiqueta" for="weekday-{{ $editando ? $disponibilidad->id : 'nueva' }}">Día de la semana</label>
        <div class="campo__control">
            <x-icono nombre="calendario" :tamano="18" class="campo__icono" />
            <select
                class="campo__entrada"
                id="weekday-{{ $editando ? $disponibilidad->id : 'nueva' }}"
                name="weekday"
                required
                @error('weekday') aria-invalid="true" aria-describedby="weekday-error" @enderror
            >
                @foreach ($diasSemana as $dia)
                    <option value="{{ $dia->value }}" @selected($weekdayActual === $dia->value)>{{ $dia->label() }}</option>
                @endforeach
            </select>
        </div>
        @error('weekday')
            <p class="campo__error" id="weekday-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="campo @error('start_time') campo--error @enderror">
        <label class="campo__etiqueta" for="start_time-{{ $editando ? $disponibilidad->id : 'nueva' }}">Hora de inicio</label>
        <div class="campo__control">
            <x-icono nombre="reloj" :tamano="18" class="campo__icono" />
            <input
                class="campo__entrada"
                id="start_time-{{ $editando ? $disponibilidad->id : 'nueva' }}"
                name="start_time"
                type="time"
                value="{{ $horaInicio }}"
                step="300"
                required
                @error('start_time') aria-invalid="true" aria-describedby="start_time-error" @enderror
            >
        </div>
        @error('start_time')
            <p class="campo__error" id="start_time-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="campo @error('end_time') campo--error @enderror">
        <label class="campo__etiqueta" for="end_time-{{ $editando ? $disponibilidad->id : 'nueva' }}">Hora de finalización</label>
        <div class="campo__control">
            <x-icono nombre="reloj" :tamano="18" class="campo__icono" />
            <input
                class="campo__entrada"
                id="end_time-{{ $editando ? $disponibilidad->id : 'nueva' }}"
                name="end_time"
                type="time"
                value="{{ $horaFin }}"
                step="300"
                required
                @error('end_time') aria-invalid="true" aria-describedby="end_time-error" @enderror
            >
        </div>
        @error('end_time')
            <p class="campo__error" id="end_time-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="campo @error('appointment_duration') campo--error @enderror">
        <label class="campo__etiqueta" for="appointment_duration-{{ $editando ? $disponibilidad->id : 'nueva' }}">Duración de consulta</label>
        <div class="campo__control">
            <x-icono nombre="reloj" :tamano="18" class="campo__icono" />
            <input
                class="campo__entrada"
                id="appointment_duration-{{ $editando ? $disponibilidad->id : 'nueva' }}"
                name="appointment_duration"
                type="number"
                value="{{ $duracion }}"
                min="5"
                max="240"
                step="5"
                inputmode="numeric"
                required
                @error('appointment_duration') aria-invalid="true" aria-describedby="appointment_duration-error" @enderror
            >
        </div>
        <p class="campo__ayuda">En minutos. Debe dividir exactamente la franja.</p>
        @error('appointment_duration')
            <p class="campo__error" id="appointment_duration-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="campo campo--completo">
        <label class="admin-check admin-check--compacto" for="is_active-{{ $editando ? $disponibilidad->id : 'nueva' }}">
            <input type="hidden" name="is_active" value="0">
            <input
                id="is_active-{{ $editando ? $disponibilidad->id : 'nueva' }}"
                name="is_active"
                type="checkbox"
                value="1"
                @checked($activo)
            >
            <span>
                <strong>Franja activa</strong>
                <small>Si está inactiva no se generan turnos ni se permiten nuevas reservas en este horario.</small>
            </span>
        </label>
    </div>
</div>
