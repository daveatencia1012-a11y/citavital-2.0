@extends('layouts.administrativo')

@section('titulo', 'Horarios')
@section('descripcion', 'Edición de una franja de disponibilidad médica en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Agenda médica</p>
                <div class="admin-paciente-titulo">
                    <h2>Editar franja</h2>
                    <x-estado-registro :activo="$disponibilidad->is_active" />
                </div>
                <p>{{ $doctor->full_name }} · {{ \App\Enums\Weekday::tryFrom($disponibilidad->weekday)?->label() }} de {{ substr((string) $disponibilidad->start_time, 0, 5) }} a {{ substr((string) $disponibilidad->end_time, 0, 5) }}</p>
            </div>
            <a class="boton boton--secundario" href="{{ route('administrativo.horarios.index', ['doctor' => $doctor->id]) }}">
                <x-icono nombre="flecha-izquierda" :tamano="17" />
                Volver a horarios
            </a>
        </div>

        @if ($citasFuturas->isNotEmpty())
            <div class="admin-alerta admin-alerta--advertencia" role="status">
                <x-icono nombre="alerta" :tamano="20" />
                <div>
                    <strong>Esta franja cubre {{ $citasFuturas->count() }} {{ $citasFuturas->count() === 1 ? 'cita futura' : 'citas futuras' }}</strong>
                    <p>Si el cambio deja esas citas sin una disponibilidad válida, la operación será bloqueada. No se cancelarán ni reprogramarán automáticamente.</p>
                    <ul>
                        @foreach ($citasFuturas->take(5) as $cita)
                            <li>{{ $cita->reference }} · {{ $cita->scheduled_at->format('d/m/Y H:i') }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form class="admin-formulario" method="POST" action="{{ route('administrativo.horarios.disponibilidades.update', $disponibilidad) }}">
            @csrf
            @method('PUT')

            <section class="admin-panel" aria-labelledby="titulo-editar-disponibilidad">
                <header class="admin-panel__cabecera">
                    <div>
                        <p class="admin-panel__sobre">Disponibilidad semanal</p>
                        <h3 id="titulo-editar-disponibilidad">Datos de la franja</h3>
                        <p>La franja no puede superponerse con otra franja activa del mismo médico.</p>
                    </div>
                </header>

                <div class="admin-formulario__campos">
                    @include('administrativo.horarios.partials.campos-disponibilidad', ['disponibilidad' => $disponibilidad])
                </div>

                <footer class="admin-formulario__acciones">
                    <p class="admin-formulario__nota">
                        <x-icono nombre="escudo" :tamano="16" />
                        Si hay citas futuras afectadas, el servidor impedirá el cambio.
                    </p>
                    <div class="admin-formulario__botones">
                        <a class="boton boton--secundario" href="{{ route('administrativo.horarios.index', ['doctor' => $doctor->id]) }}">Cancelar</a>
                        <button class="boton boton--primario" type="submit">
                            <x-icono nombre="check-circulo" :tamano="18" />
                            Guardar cambios
                        </button>
                    </div>
                </footer>
            </section>
        </form>

        <section class="admin-panel admin-panel--peligro" aria-labelledby="titulo-eliminar-disponibilidad">
            <header class="admin-panel__cabecera">
                <div>
                    <p class="admin-panel__sobre">Acción irreversible</p>
                    <h3 id="titulo-eliminar-disponibilidad">Eliminar franja</h3>
                    <p>La eliminación solo se permite si no deja citas futuras sin disponibilidad válida.</p>
                </div>
            </header>
            <div class="admin-panel__cuerpo admin-panel__cuerpo--peligro">
                <p>Los turnos y citas existentes no se cancelan automáticamente. Si hay citas afectadas, conserva la franja y reprograma primero.</p>
                <form
                    method="POST"
                    action="{{ route('administrativo.horarios.disponibilidades.destroy', $disponibilidad) }}"
                    data-confirm="¿Eliminar definitivamente esta franja? Si tiene citas futuras, la operación será bloqueada."
                >
                    @csrf
                    @method('DELETE')
                    <button class="boton boton--peligro" type="submit">
                        <x-icono nombre="basura" :tamano="18" />
                        Eliminar franja
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
