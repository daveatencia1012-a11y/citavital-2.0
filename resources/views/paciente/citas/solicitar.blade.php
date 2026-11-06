@extends('layouts.paciente')

@section('titulo', 'Solicitar cita')
@section('descripcion', 'Solicitud de cita médica paso a paso en el portal del paciente de CitaVital.')

@php
    $pasoActual = 1;

    if ($especialidadSeleccionada !== null) {
        $pasoActual = 2;
    }

    if ($doctorSeleccionado !== null) {
        $pasoActual = 3;
    }

    if ($fechaSeleccionada !== null) {
        $pasoActual = 4;
    }

    if ($turnoSeleccionado !== null) {
        $pasoActual = 5;
    }

    $pasos = [
        1 => 'Especialidad',
        2 => 'Médico',
        3 => 'Fecha',
        4 => 'Horario',
        5 => 'Confirmación',
    ];
@endphp

@section('contenido')
    <div class="paciente-contenido">
        <header class="paciente-pagina__cabecera">
            <div>
                <p class="paciente-sobre">Nueva solicitud</p>
                <h1>Solicitar una cita médica</h1>
                <p>Completa los pasos para elegir especialidad, médico, fecha y horario. La reserva se valida nuevamente al confirmar.</p>
            </div>
            <span class="paciente-pagina__sello">
                <x-icono nombre="reloj" :tamano="17" />
                Hora de Bogotá
            </span>
        </header>

        <ol class="paciente-pasos" aria-label="Pasos para solicitar una cita">
            @foreach ($pasos as $numero => $nombre)
                @php
                    $estadoPaso = $numero < $pasoActual ? 'completado' : ($numero === $pasoActual ? 'activo' : 'pendiente');
                @endphp
                <li class="paciente-paso paciente-paso--{{ $estadoPaso }}" @if ($numero === $pasoActual) aria-current="step" @endif>
                    <span class="paciente-paso__numero">
                        @if ($numero < $pasoActual)
                            <x-icono nombre="check" :tamano="15" />
                        @else
                            {{ $numero }}
                        @endif
                    </span>
                    <span class="paciente-paso__nombre">{{ $nombre }}</span>
                </li>
            @endforeach
        </ol>

        <div class="paciente-asistente">
            <section class="paciente-panel paciente-panel--paso" aria-labelledby="paso-especialidad">
                <header class="paciente-panel__cabecera">
                    <div>
                        <p class="paciente-sobre">Paso 1</p>
                        <h2 id="paso-especialidad">Selecciona la especialidad</h2>
                        <p>Se muestran únicamente las especialidades activas.</p>
                    </div>
                    @if ($especialidadSeleccionada !== null)
                        <a class="paciente-enlace" href="{{ route('paciente.citas.solicitar') }}">Cambiar</a>
                    @endif
                </header>

                @if ($especialidadSeleccionada !== null)
                    <div class="paciente-seleccion">
                        <span class="paciente-seleccion__icono"><x-icono nombre="institucion" :tamano="20" /></span>
                        <div>
                            <strong>{{ $especialidadSeleccionada->name }}</strong>
                            <p>{{ $especialidadSeleccionada->description }}</p>
                        </div>
                    </div>
                @elseif ($especialidades->isEmpty())
                    <div class="paciente-vacio paciente-vacio--compacto">
                        <p>No hay especialidades activas disponibles en este momento. Intenta nuevamente más tarde.</p>
                    </div>
                @else
                    <form method="get" action="{{ route('paciente.citas.solicitar') }}" class="paciente-formulario">
                        <fieldset class="paciente-opciones">
                            <legend class="visualmente-oculto">Especialidades disponibles</legend>
                            @foreach ($especialidades as $especialidad)
                                <label class="paciente-opcion">
                                    <input class="paciente-opcion__control" type="radio" name="specialty" value="{{ $especialidad->id }}" required>
                                    <span class="paciente-opcion__contenido">
                                        <strong>{{ $especialidad->name }}</strong>
                                        <small>{{ $especialidad->description }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </fieldset>
                        <button class="boton boton--primario" type="submit">Continuar con el médico</button>
                    </form>
                @endif
            </section>

            @if ($especialidadSeleccionada !== null)
                <section class="paciente-panel paciente-panel--paso" aria-labelledby="paso-medico">
                    <header class="paciente-panel__cabecera">
                        <div>
                            <p class="paciente-sobre">Paso 2</p>
                            <h2 id="paso-medico">Selecciona el médico</h2>
                            <p>Estos médicos activos atienden la especialidad de {{ $especialidadSeleccionada->name }}.</p>
                        </div>
                        @if ($doctorSeleccionado !== null)
                            <a class="paciente-enlace" href="{{ route('paciente.citas.solicitar', ['specialty' => $especialidadSeleccionada->id]) }}">Cambiar</a>
                        @endif
                    </header>

                    @if ($doctorSeleccionado !== null)
                        <div class="paciente-seleccion">
                            <span class="paciente-seleccion__icono"><x-icono nombre="estetoscopio" :tamano="20" /></span>
                            <div>
                                <strong>{{ $doctorSeleccionado->full_name }}</strong>
                                <p>Código interno {{ $doctorSeleccionado->internal_code }}</p>
                            </div>
                        </div>
                    @elseif ($doctores->isEmpty())
                        <div class="paciente-vacio paciente-vacio--compacto">
                            <p>No hay médicos activos asociados a esta especialidad. Elige otra especialidad.</p>
                        </div>
                    @else
                        <form method="get" action="{{ route('paciente.citas.solicitar') }}" class="paciente-formulario">
                            <input type="hidden" name="specialty" value="{{ $especialidadSeleccionada->id }}">
                            <fieldset class="paciente-opciones">
                                <legend class="visualmente-oculto">Médicos disponibles</legend>
                                @foreach ($doctores as $doctor)
                                    <label class="paciente-opcion">
                                        <input class="paciente-opcion__control" type="radio" name="doctor" value="{{ $doctor->id }}" required>
                                        <span class="paciente-opcion__contenido">
                                            <strong>{{ $doctor->full_name }}</strong>
                                            <small>{{ $doctor->specialties->pluck('name')->implode(' · ') }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <button class="boton boton--primario" type="submit">Continuar con la fecha</button>
                        </form>
                    @endif
                </section>
            @endif

            @if ($doctorSeleccionado !== null)
                <section class="paciente-panel paciente-panel--paso" aria-labelledby="paso-fecha">
                    <header class="paciente-panel__cabecera">
                        <div>
                            <p class="paciente-sobre">Paso 3</p>
                            <h2 id="paso-fecha">Selecciona la fecha</h2>
                            <p>No se admiten fechas pasadas. La agenda se muestra en hora de Bogotá.</p>
                        </div>
                        @if ($fechaSeleccionada !== null)
                            <a class="paciente-enlace" href="{{ route('paciente.citas.solicitar', ['specialty' => $especialidadSeleccionada->id, 'doctor' => $doctorSeleccionado->id]) }}">Cambiar</a>
                        @endif
                    </header>

                    @if ($fechaInvalida)
                        <div class="paciente-alerta paciente-alerta--error" role="alert">
                            <x-icono nombre="alerta" :tamano="20" />
                            <div>
                                <strong>Fecha no válida</strong>
                                <p>Selecciona una fecha igual o posterior a hoy.</p>
                            </div>
                        </div>
                    @endif

                    @if ($fechaSeleccionada !== null)
                        <div class="paciente-seleccion">
                            <span class="paciente-seleccion__icono"><x-icono nombre="calendario" :tamano="20" /></span>
                            <div>
                                <strong>{{ $fechaSeleccionada->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}</strong>
                                <p>{{ $doctorSeleccionado->full_name }} · {{ $especialidadSeleccionada->name }}</p>
                            </div>
                        </div>
                    @else
                        <form method="get" action="{{ route('paciente.citas.solicitar') }}" class="paciente-formulario paciente-formulario--fecha">
                            <input type="hidden" name="specialty" value="{{ $especialidadSeleccionada->id }}">
                            <input type="hidden" name="doctor" value="{{ $doctorSeleccionado->id }}">
                            <div class="campo">
                                <label class="campo__etiqueta" for="fecha">Fecha de la cita</label>
                                <input class="campo__entrada paciente-campo-fecha" id="fecha" name="date" type="date" min="{{ $hoy }}" value="{{ $fechaInvalida ? '' : $fechaInput }}" required>
                                <p class="campo__ayuda">Solo puedes elegir hoy o una fecha futura.</p>
                            </div>
                            <button class="boton boton--primario" type="submit">Consultar horarios</button>
                        </form>
                    @endif
                </section>
            @endif

            @if ($fechaSeleccionada !== null)
                <section class="paciente-panel paciente-panel--paso" aria-labelledby="paso-horario">
                    <header class="paciente-panel__cabecera">
                        <div>
                            <p class="paciente-sobre">Paso 4</p>
                            <h2 id="paso-horario">Selecciona el horario</h2>
                            <p>Horarios disponibles según la disponibilidad del médico, sus bloqueos y las citas ya reservadas.</p>
                        </div>
                        @if ($turnoSeleccionado !== null)
                            <a class="paciente-enlace" href="{{ route('paciente.citas.solicitar', ['specialty' => $especialidadSeleccionada->id, 'doctor' => $doctorSeleccionado->id, 'date' => $fechaSeleccionada->toDateString()]) }}">Cambiar</a>
                        @endif
                    </header>

                    @if ($turnos->isEmpty())
                        <div class="paciente-vacio paciente-vacio--compacto">
                            <span class="paciente-vacio__icono"><x-icono nombre="reloj" :tamano="24" /></span>
                            <h3>No hay turnos disponibles para esta fecha.</h3>
                            <p>Prueba con otro día o consulta nuevamente más tarde. La agenda puede cambiar si se ocupa un turno.</p>
                            <a class="boton boton--secundario" href="{{ route('paciente.citas.solicitar', ['specialty' => $especialidadSeleccionada->id, 'doctor' => $doctorSeleccionado->id]) }}">Elegir otra fecha</a>
                        </div>
                    @elseif ($turnoSeleccionado !== null)
                        <div class="paciente-seleccion">
                            <span class="paciente-seleccion__icono"><x-icono nombre="reloj" :tamano="20" /></span>
                            <div>
                                <strong>{{ $turnoSeleccionado->starts_at->format('H:i') }} – {{ $turnoSeleccionado->ends_at->format('H:i') }}</strong>
                                <p>{{ $fechaSeleccionada->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}</p>
                            </div>
                        </div>
                    @else
                        <form method="get" action="{{ route('paciente.citas.solicitar') }}" class="paciente-formulario">
                            <input type="hidden" name="specialty" value="{{ $especialidadSeleccionada->id }}">
                            <input type="hidden" name="doctor" value="{{ $doctorSeleccionado->id }}">
                            <input type="hidden" name="date" value="{{ $fechaSeleccionada->toDateString() }}">
                            <fieldset class="paciente-turnos">
                                <legend class="visualmente-oculto">Turnos disponibles</legend>
                                @foreach ($turnos as $turno)
                                    <label class="paciente-turno">
                                        <input class="paciente-opcion__control" type="radio" name="slot" value="{{ $turno->id }}" required>
                                        <span>{{ $turno->starts_at->format('H:i') }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <button class="boton boton--primario" type="submit">Continuar a confirmación</button>
                        </form>
                    @endif
                </section>
            @endif

            @if ($turnoSeleccionado !== null)
                <section class="paciente-panel paciente-panel--paso paciente-panel--confirmacion" aria-labelledby="paso-confirmacion">
                    <header class="paciente-panel__cabecera">
                        <div>
                            <p class="paciente-sobre">Paso 5</p>
                            <h2 id="paso-confirmacion">Confirma tu reserva</h2>
                            <p>Revisa la información antes de registrar la cita. El servidor validará nuevamente la disponibilidad.</p>
                        </div>
                    </header>

                    <dl class="paciente-resumen">
                        <div>
                            <dt>Paciente</dt>
                            <dd>{{ $paciente->full_name }} · Cédula {{ $paciente->document_number }}</dd>
                        </div>
                        <div>
                            <dt>Especialidad</dt>
                            <dd>{{ $especialidadSeleccionada->name }}</dd>
                        </div>
                        <div>
                            <dt>Médico</dt>
                            <dd>{{ $doctorSeleccionado->full_name }}</dd>
                        </div>
                        <div>
                            <dt>Fecha</dt>
                            <dd>{{ $fechaSeleccionada->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}</dd>
                        </div>
                        <div>
                            <dt>Hora</dt>
                            <dd>{{ $turnoSeleccionado->starts_at->format('H:i') }} – {{ $turnoSeleccionado->ends_at->format('H:i') }} (America/Bogota)</dd>
                        </div>
                    </dl>

                    <form method="post" action="{{ route('paciente.citas.store') }}" class="paciente-formulario paciente-formulario--confirmar">
                        @csrf
                        <input type="hidden" name="specialty_id" value="{{ $especialidadSeleccionada->id }}">
                        <input type="hidden" name="doctor_id" value="{{ $doctorSeleccionado->id }}">
                        <input type="hidden" name="slot_id" value="{{ $turnoSeleccionado->id }}">
                        <input type="hidden" name="date" value="{{ $fechaSeleccionada->toDateString() }}">

                        <div class="paciente-nota">
                            <x-icono nombre="escudo" :tamano="18" />
                            <p>La cita se registrará únicamente para tu sesión. No se aceptan identificadores de otros pacientes desde el formulario.</p>
                        </div>

                        <div class="paciente-formulario__acciones">
                            <a class="boton boton--secundario" href="{{ route('paciente.citas.solicitar', ['specialty' => $especialidadSeleccionada->id, 'doctor' => $doctorSeleccionado->id, 'date' => $fechaSeleccionada->toDateString()]) }}">Elegir otro horario</a>
                            <button class="boton boton--primario" type="submit">
                                <x-icono nombre="check-circulo" :tamano="18" />
                                Confirmar cita
                            </button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
@endsection
