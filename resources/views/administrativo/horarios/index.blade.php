@extends('layouts.administrativo')

@section('titulo', 'Horarios')
@section('descripcion', 'Configuración de disponibilidad, bloqueos y agenda médica en CitaVital.')

@section('contenido')
    @php
        $etiquetasTurno = [
            'disponible' => 'Disponible',
            'reservado' => 'Reservado',
            'bloqueado' => 'Bloqueado',
            'pasado' => 'Horario pasado',
            'no_disponible' => 'No disponible',
        ];
    @endphp

    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Agenda médica</p>
                <h2>Horarios</h2>
                <p>Configura la disponibilidad semanal, registra bloqueos y genera los turnos de cada médico.</p>
            </div>
        </div>

        @if ($doctores->isEmpty())
            <section class="admin-panel">
                <div class="admin-vacio">
                    <span class="admin-vacio__icono">
                        <x-icono nombre="reloj" :tamano="26" />
                    </span>
                    <h4>No hay médicos activos para configurar horarios.</h4>
                    <p>Activa o registra un médico para comenzar a construir su agenda.</p>
                    <a class="boton boton--primario" href="{{ route('administrativo.medicos.index') }}">Gestionar médicos</a>
                </div>
            </section>
        @else
            <section class="admin-panel admin-horario-selector" aria-labelledby="titulo-selector-horario">
                <header class="admin-panel__cabecera">
                    <div>
                        <p class="admin-panel__sobre">Selección de agenda</p>
                        <h3 id="titulo-selector-horario">Médico y rango de visualización</h3>
                        <p>Al elegir un médico se muestra su disponibilidad, bloqueos y turnos. Las fechas y horas se manejan en America/Bogota.</p>
                    </div>
                </header>

                <form class="admin-horario-filtro" method="GET" action="{{ route('administrativo.horarios.index') }}" data-horario-filtro>
                    <div class="campo">
                        <label class="campo__etiqueta" for="doctor">Médico</label>
                        <div class="campo__control">
                            <x-icono nombre="estetoscopio" :tamano="18" class="campo__icono" />
                            <select class="campo__entrada" id="doctor" name="doctor" data-horario-doctor>
                                @foreach ($doctores as $doctor)
                                    <option value="{{ $doctor->id }}" @selected($doctorSeleccionado?->id === $doctor->id)>
                                        {{ $doctor->full_name }} · {{ $doctor->internal_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="campo">
                        <label class="campo__etiqueta" for="desde">Desde</label>
                        <div class="campo__control">
                            <x-icono nombre="calendario" :tamano="18" class="campo__icono" />
                            <input class="campo__entrada" id="desde" name="desde" type="date" value="{{ $desde->toDateString() }}">
                        </div>
                    </div>

                    <div class="campo">
                        <label class="campo__etiqueta" for="hasta">Hasta</label>
                        <div class="campo__control">
                            <x-icono nombre="calendario" :tamano="18" class="campo__icono" />
                            <input class="campo__entrada" id="hasta" name="hasta" type="date" value="{{ $hasta->toDateString() }}">
                        </div>
                    </div>

                    <button class="boton boton--primario admin-horario-filtro__boton" type="submit">Ver agenda</button>
                </form>
            </section>

            @if ($doctorSeleccionado !== null)
                <section class="admin-doctor-card" aria-label="Médico seleccionado">
                    <span class="admin-doctor-card__icono">
                        <x-icono nombre="estetoscopio" :tamano="28" />
                    </span>
                    <div class="admin-doctor-card__datos">
                        <p class="admin-panel__sobre">Médico seleccionado</p>
                        <h3>{{ $doctorSeleccionado->full_name }}</h3>
                        <p>{{ $doctorSeleccionado->internal_code }} · Agenda médica</p>
                        <div class="admin-etiquetas">
                            @forelse ($doctorSeleccionado->specialties as $especialidad)
                                <span class="admin-etiqueta {{ $especialidad->is_active ? '' : 'admin-etiqueta--inactiva' }}">
                                    {{ $especialidad->name }}
                                </span>
                            @empty
                                <span class="admin-tabla__secundario">Sin especialidades asignadas</span>
                            @endforelse
                        </div>
                    </div>
                    <x-estado-registro :activo="$doctorSeleccionado->is_active" />
                </section>

                <div class="admin-horario-columnas">
                    <section class="admin-panel" id="disponibilidad" aria-labelledby="titulo-disponibilidad">
                        <header class="admin-panel__cabecera">
                            <div>
                                <p class="admin-panel__sobre">Agenda semanal</p>
                                <h3 id="titulo-disponibilidad">Disponibilidad semanal</h3>
                                <p>Cada franja activa genera turnos según la duración indicada.</p>
                            </div>
                            <span class="admin-panel__contador">
                                {{ $disponibilidades->count() }} {{ $disponibilidades->count() === 1 ? 'franja' : 'franjas' }}
                            </span>
                        </header>

                        @if ($disponibilidades->isEmpty())
                            <div class="admin-vacio admin-vacio--compacto">
                                <span class="admin-vacio__icono">
                                    <x-icono nombre="reloj" :tamano="22" />
                                </span>
                                <h4>Sin disponibilidad configurada.</h4>
                                <p>Agrega la primera franja semanal para este médico.</p>
                            </div>
                        @else
                            <div class="admin-tabla-contenedor">
                                <table class="admin-tabla admin-tabla--horarios">
                                    <caption class="visualmente-oculto">Disponibilidad semanal del médico</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Día</th>
                                            <th scope="col">Horario</th>
                                            <th scope="col">Duración</th>
                                            <th scope="col">Citas futuras</th>
                                            <th scope="col">Estado</th>
                                            <th scope="col">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($disponibilidades as $disponibilidad)
                                            @php
                                                $dia = \App\Enums\Weekday::tryFrom($disponibilidad->weekday);
                                                $citasFuturas = $citasFuturasPorDisponibilidad[$disponibilidad->id] ?? 0;
                                            @endphp
                                            <tr>
                                                <td><span class="admin-tabla__principal">{{ $dia?->label() ?? '—' }}</span></td>
                                                <td>
                                                    <span class="admin-horario-rango">
                                                        {{ substr((string) $disponibilidad->start_time, 0, 5) }}
                                                        <span>a</span>
                                                        {{ substr((string) $disponibilidad->end_time, 0, 5) }}
                                                    </span>
                                                </td>
                                                <td>{{ $disponibilidad->appointment_duration }} min</td>
                                                <td>
                                                    @if ($citasFuturas > 0)
                                                        <span class="admin-badge admin-badge--advertencia">
                                                            {{ $citasFuturas }} {{ $citasFuturas === 1 ? 'cita' : 'citas' }}
                                                        </span>
                                                    @else
                                                        <span class="admin-tabla__secundario">—</span>
                                                    @endif
                                                </td>
                                                <td><x-estado-registro :activo="$disponibilidad->is_active" /></td>
                                                <td>
                                                    <div class="admin-acciones">
                                                        <a
                                                            class="admin-accion"
                                                            href="{{ route('administrativo.horarios.disponibilidades.edit', $disponibilidad) }}"
                                                            title="Editar franja"
                                                            aria-label="Editar franja del {{ $dia?->label() }}"
                                                        >
                                                            <x-icono nombre="lapiz" :tamano="17" />
                                                        </a>

                                                        @if ($disponibilidad->is_active)
                                                            <form
                                                                method="POST"
                                                                action="{{ route('administrativo.horarios.disponibilidades.estado', $disponibilidad) }}"
                                                                data-confirm="¿Desactivar esta franja?{{ $citasFuturas > 0 ? ' Tiene '.$citasFuturas.' citas futuras y no se cancelarán automáticamente.' : '' }}"
                                                            >
                                                                @csrf
                                                                @method('PATCH')
                                                                <button class="admin-accion admin-accion--peligro" type="submit" title="Desactivar franja" aria-label="Desactivar franja">
                                                                    <x-icono nombre="cerrar" :tamano="17" />
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form method="POST" action="{{ route('administrativo.horarios.disponibilidades.estado', $disponibilidad) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button class="admin-accion admin-accion--exito" type="submit" title="Reactivar franja" aria-label="Reactivar franja">
                                                                    <x-icono nombre="check-circulo" :tamano="17" />
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form
                                                            method="POST"
                                                            action="{{ route('administrativo.horarios.disponibilidades.destroy', $disponibilidad) }}"
                                                            data-confirm="¿Eliminar definitivamente esta franja? Si tiene citas futuras, la operación será bloqueada."
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="admin-accion admin-accion--peligro" type="submit" title="Eliminar franja" aria-label="Eliminar franja">
                                                                <x-icono nombre="basura" :tamano="17" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <form class="admin-horario-form" method="POST" action="{{ route('administrativo.horarios.disponibilidades.store') }}">
                            @csrf
                            <input type="hidden" name="doctor_id" value="{{ $doctorSeleccionado->id }}">
                            <div class="admin-horario-form__cabecera">
                                <h4>Agregar franja semanal</h4>
                                <p>Ejemplo: lunes de 08:00 a 12:00 con consultas de 30 minutos genera turnos cada media hora.</p>
                            </div>
                            @include('administrativo.horarios.partials.campos-disponibilidad', ['disponibilidad' => null])
                            <div class="admin-horario-form__acciones">
                                <button class="boton boton--primario" type="submit">
                                    <x-icono nombre="check-circulo" :tamano="18" />
                                    Agregar franja
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="admin-panel" id="bloqueos" aria-labelledby="titulo-bloqueos">
                        <header class="admin-panel__cabecera">
                            <div>
                                <p class="admin-panel__sobre">Excepciones</p>
                                <h3 id="titulo-bloqueos">Bloqueos y ausencias</h3>
                                <p>Registra descansos, ausencias, vacaciones o bloqueos por fecha y rango horario.</p>
                            </div>
                            <span class="admin-panel__contador">
                                {{ $bloqueos->count() }} {{ $bloqueos->count() === 1 ? 'bloqueo' : 'bloqueos' }}
                            </span>
                        </header>

                        @if ($bloqueos->isEmpty())
                            <div class="admin-vacio admin-vacio--compacto">
                                <span class="admin-vacio__icono">
                                    <x-icono nombre="escudo" :tamano="22" />
                                </span>
                                <h4>Sin bloqueos registrados.</h4>
                                <p>No se generarán turnos durante los períodos bloqueados.</p>
                            </div>
                        @else
                            <div class="admin-tabla-contenedor">
                                <table class="admin-tabla admin-tabla--bloqueos">
                                    <caption class="visualmente-oculto">Bloqueos de agenda del médico</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Motivo</th>
                                            <th scope="col">Período</th>
                                            <th scope="col">Estado</th>
                                            <th scope="col">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($bloqueos as $bloqueo)
                                            <tr>
                                                <td><span class="admin-tabla__principal">{{ $bloqueo->reason ?: 'Bloqueo de agenda' }}</span></td>
                                                <td>
                                                    <span class="admin-bloqueo-rango">
                                                        {{ $bloqueo->starts_at->format('d/m/Y H:i') }}
                                                        <span>→</span>
                                                        {{ $bloqueo->ends_at->format('d/m/Y H:i') }}
                                                    </span>
                                                </td>
                                                <td><x-estado-registro :activo="$bloqueo->is_active" textoActivo="Activo" textoInactivo="Inactivo" /></td>
                                                <td>
                                                    <div class="admin-acciones">
                                                        <form method="POST" action="{{ route('administrativo.horarios.bloqueos.estado', $bloqueo) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button
                                                                class="admin-accion {{ $bloqueo->is_active ? 'admin-accion--peligro' : 'admin-accion--exito' }}"
                                                                type="submit"
                                                                title="{{ $bloqueo->is_active ? 'Desactivar bloqueo' : 'Reactivar bloqueo' }}"
                                                                aria-label="{{ $bloqueo->is_active ? 'Desactivar bloqueo' : 'Reactivar bloqueo' }}"
                                                            >
                                                                <x-icono :nombre="$bloqueo->is_active ? 'cerrar' : 'check-circulo'" :tamano="17" />
                                                            </button>
                                                        </form>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('administrativo.horarios.bloqueos.destroy', $bloqueo) }}"
                                                            data-confirm="¿Eliminar este bloqueo? Los turnos ya existentes no se modifican automáticamente."
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="admin-accion admin-accion--peligro" type="submit" title="Eliminar bloqueo" aria-label="Eliminar bloqueo">
                                                                <x-icono nombre="basura" :tamano="17" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <form class="admin-horario-form" method="POST" action="{{ route('administrativo.horarios.bloqueos.store') }}">
                            @csrf
                            <input type="hidden" name="doctor_id" value="{{ $doctorSeleccionado->id }}">
                            <div class="admin-horario-form__cabecera">
                                <h4>Registrar bloqueo</h4>
                                <p>No se crearán turnos en el período bloqueado. Si afecta citas futuras, la operación se bloqueará.</p>
                            </div>

                            <div class="admin-horario-form__grid">
                                <div class="campo @error('tipo') campo--error @enderror">
                                    <label class="campo__etiqueta" for="tipo">Tipo de bloqueo</label>
                                    <div class="campo__control">
                                        <x-icono nombre="escudo" :tamano="18" class="campo__icono" />
                                        <select class="campo__entrada" id="tipo" name="tipo" required>
                                            @foreach ($tiposBloqueo as $valor => $etiqueta)
                                                <option value="{{ $valor }}" @selected(old('tipo', 'descanso') === $valor)>{{ $etiqueta }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('tipo')
                                        <p class="campo__error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="campo @error('motivo') campo--error @enderror">
                                    <label class="campo__etiqueta" for="motivo">Detalle</label>
                                    <div class="campo__control">
                                        <x-icono nombre="documento" :tamano="18" class="campo__icono" />
                                        <input class="campo__entrada" id="motivo" name="motivo" type="text" value="{{ old('motivo') }}" maxlength="150" placeholder="Opcional. Ejemplo: descanso de mediodía">
                                    </div>
                                    @error('motivo')
                                        <p class="campo__error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="campo @error('starts_at') campo--error @enderror">
                                    <label class="campo__etiqueta" for="starts_at">Inicio</label>
                                    <div class="campo__control">
                                        <x-icono nombre="calendario" :tamano="18" class="campo__icono" />
                                        <input class="campo__entrada" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" required>
                                    </div>
                                    @error('starts_at')
                                        <p class="campo__error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="campo @error('ends_at') campo--error @enderror">
                                    <label class="campo__etiqueta" for="ends_at">Fin</label>
                                    <div class="campo__control">
                                        <x-icono nombre="calendario" :tamano="18" class="campo__icono" />
                                        <input class="campo__entrada" id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" required>
                                    </div>
                                    @error('ends_at')
                                        <p class="campo__error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="admin-horario-form__acciones">
                                <button class="boton boton--primario" type="submit">
                                    <x-icono nombre="check-circulo" :tamano="18" />
                                    Registrar bloqueo
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <section class="admin-panel" id="agenda" aria-labelledby="titulo-agenda">
                    <header class="admin-panel__cabecera">
                        <div>
                            <p class="admin-panel__sobre">Agenda generada</p>
                            <h3 id="titulo-agenda">Turnos del médico</h3>
                            <p>Visualiza los turnos disponibles, reservados, bloqueados y pasados del rango seleccionado.</p>
                        </div>
                        <span class="admin-panel__contador">
                            {{ $turnos->count() }} {{ $turnos->count() === 1 ? 'turno' : 'turnos' }}
                        </span>
                    </header>

                    <form class="admin-agenda__generar" method="POST" action="{{ route('administrativo.horarios.generar') }}">
                        @csrf
                        <input type="hidden" name="doctor_id" value="{{ $doctorSeleccionado->id }}">
                        <div class="campo">
                            <label class="campo__etiqueta" for="generar_desde">Generar desde</label>
                            <div class="campo__control">
                                <x-icono nombre="calendario" :tamano="18" class="campo__icono" />
                                <input class="campo__entrada" id="generar_desde" name="desde" type="date" value="{{ $desde->toDateString() }}" min="{{ $hoy }}" required>
                            </div>
                        </div>
                        <div class="campo">
                            <label class="campo__etiqueta" for="generar_hasta">Generar hasta</label>
                            <div class="campo__control">
                                <x-icono nombre="calendario" :tamano="18" class="campo__icono" />
                                <input class="campo__entrada" id="generar_hasta" name="hasta" type="date" value="{{ $hasta->toDateString() }}" min="{{ $hoy }}" required>
                            </div>
                        </div>
                        <button class="boton boton--primario admin-agenda__generar-boton" type="submit">
                            <x-icono nombre="calendario" :tamano="18" />
                            Generar turnos
                        </button>
                    </form>

                    <div class="admin-turnos-leyenda" aria-label="Convenciones de los turnos">
                        @foreach ($etiquetasTurno as $clave => $etiqueta)
                            <span class="admin-turno-leyenda admin-turno-leyenda--{{ $clave }}">
                                <span aria-hidden="true"></span>
                                {{ $etiqueta }}
                            </span>
                        @endforeach
                    </div>

                    @if ($turnos->isEmpty())
                        <div class="admin-vacio">
                            <span class="admin-vacio__icono">
                                <x-icono nombre="calendario" :tamano="26" />
                            </span>
                            <h4>No hay turnos generados en este rango.</h4>
                            <p>Configura una franja activa y usa «Generar turnos» para crear la agenda del médico.</p>
                        </div>
                    @else
                        <div class="admin-agenda">
                            @foreach ($turnosPorDia as $fecha => $turnosDia)
                                <section class="admin-agenda__dia" aria-label="Turnos del {{ $fecha }}">
                                    <header class="admin-agenda__dia-cabecera">
                                        <h4>{{ \Illuminate\Support\Str::ucfirst(\Carbon\CarbonImmutable::parse($fecha)->locale('es')->translatedFormat('l, j \d\e F')) }}</h4>
                                        <span>{{ $turnosDia->count() }} {{ $turnosDia->count() === 1 ? 'turno' : 'turnos' }}</span>
                                    </header>

                                    <div class="admin-turnos">
                                        @foreach ($turnosDia as $turno)
                                            <article
                                                class="admin-turno admin-turno--{{ $turno->estado_visual }}"
                                                title="{{ $turno->starts_at->format('H:i') }} a {{ $turno->ends_at->format('H:i') }}"
                                            >
                                                <span class="admin-turno__hora">{{ $turno->starts_at->format('H:i') }}</span>
                                                <span class="admin-turno__estado">{{ $etiquetasTurno[$turno->estado_visual] ?? $turno->estado_visual }}</span>
                                                @if ($turno->estado_visual === 'reservado' && $turno->appointment?->patient)
                                                    <a class="admin-turno__paciente" href="{{ route('administrativo.pacientes.show', $turno->appointment->patient) }}">
                                                        {{ $turno->appointment->patient->full_name }}
                                                    </a>
                                                @elseif ($turno->appointment?->specialty)
                                                    <span class="admin-turno__detalle">{{ $turno->appointment->specialty->name }}</span>
                                                @endif
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        @endif
    </div>
@endsection
