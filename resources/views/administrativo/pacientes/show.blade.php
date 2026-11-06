@extends('layouts.administrativo')

@section('titulo', 'Pacientes')
@section('descripcion', 'Ficha e historial de citas del paciente en CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        @include('administrativo.partials.alertas')

        <div class="admin-pagina__cabecera">
            <div>
                <p class="admin-pagina__sobre">Historial del paciente</p>
                <div class="admin-paciente-titulo">
                    <h2>{{ $paciente->full_name }}</h2>
                    <span class="admin-estado admin-estado--{{ $paciente->is_active ? 'activo' : 'inactivo' }}">
                        {{ $paciente->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                <p>Cédula {{ $paciente->document_number }} · {{ $paciente->age }} años</p>
            </div>

            <div class="admin-paciente-acciones">
                <a class="boton boton--secundario" href="{{ route('administrativo.pacientes.index') }}">
                    <x-icono nombre="flecha-izquierda" :tamano="17" />
                    Volver al listado
                </a>
                <a class="boton boton--primario" href="{{ route('administrativo.pacientes.edit', $paciente) }}">
                    <x-icono nombre="lapiz" :tamano="17" />
                    Editar
                </a>

                @if ($paciente->is_active)
                    <form
                        method="POST"
                        action="{{ route('administrativo.pacientes.estado', $paciente) }}"
                        data-confirm="¿Desactivar a {{ $paciente->full_name }}? El paciente conservará sus citas e historial."
                    >
                        @csrf
                        @method('PATCH')
                        <button class="boton boton--peligro" type="submit">
                            <x-icono nombre="cerrar" :tamano="17" />
                            Desactivar
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('administrativo.pacientes.estado', $paciente) }}">
                        @csrf
                        @method('PATCH')
                        <button class="boton boton--exito" type="submit">
                            <x-icono nombre="check-circulo" :tamano="17" />
                            Reactivar
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if (! $paciente->is_active)
            <div class="admin-aviso" role="status">
                <x-icono nombre="alerta" :tamano="20" />
                <div>
                    <strong>Paciente inactivo</strong>
                    <p>No podrá realizar nuevas reservas cuando se habilite el portal del paciente. Sus citas e historial se conservan.</p>
                </div>
            </div>
        @endif

        <div class="admin-pila">
            <section class="admin-panel" aria-labelledby="titulo-datos-paciente">
                <header class="admin-panel__cabecera">
                    <div>
                        <p class="admin-panel__sobre">Ficha administrativa</p>
                        <h3 id="titulo-datos-paciente">Datos del paciente</h3>
                        <p>Información personal y de contacto registrada en el sistema.</p>
                    </div>
                </header>

                <dl class="admin-datos">
                    <div>
                        <dt>Número de cédula</dt>
                        <dd>{{ $paciente->document_number }}</dd>
                    </div>
                    <div>
                        <dt>Fecha de nacimiento</dt>
                        <dd>{{ $paciente->birth_date->format('d/m/Y') }} · {{ $paciente->age }} años</dd>
                    </div>
                    <div>
                        <dt>Tipo de seguro</dt>
                        <dd><x-tipo-seguro :tipo="$paciente->insurance_type" /></dd>
                    </div>
                    <div>
                        <dt>Estado</dt>
                        <dd>
                            <span class="admin-estado admin-estado--{{ $paciente->is_active ? 'activo' : 'inactivo' }}">
                                {{ $paciente->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>Teléfono principal</dt>
                        <dd><a class="admin-telefono" href="tel:{{ $paciente->primary_phone }}">{{ $paciente->primary_phone }}</a></dd>
                    </div>
                    <div>
                        <dt>Teléfono secundario</dt>
                        <dd><a class="admin-telefono" href="tel:{{ $paciente->secondary_phone }}">{{ $paciente->secondary_phone }}</a></dd>
                    </div>
                    <div class="admin-datos__completo">
                        <dt>Dirección</dt>
                        <dd>{{ $paciente->address }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-panel" aria-labelledby="titulo-historial-citas">
                <header class="admin-panel__cabecera">
                    <div>
                        <p class="admin-panel__sobre">Trazabilidad</p>
                        <h3 id="titulo-historial-citas">Historial de citas</h3>
                        <p>Citas registradas para este paciente, de la más reciente a la más antigua.</p>
                    </div>
                    <span class="admin-panel__contador">
                        {{ $citas->total() }} {{ $citas->total() === 1 ? 'cita' : 'citas' }}
                    </span>
                </header>

                @if ($citas->isEmpty())
                    <div class="admin-vacio">
                        <span class="admin-vacio__icono">
                            <x-icono nombre="calendario" :tamano="26" />
                        </span>
                        <h4>Este paciente aún no tiene citas registradas.</h4>
                        <p>Cuando se agende una cita, aparecerá en esta sección con su médico, especialidad y estado.</p>
                    </div>
                @else
                    <div class="admin-tabla-contenedor">
                        <table class="admin-tabla admin-tabla--historial">
                            <caption class="visualmente-oculto">Historial de citas del paciente {{ $paciente->full_name }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Referencia</th>
                                    <th scope="col">Médico</th>
                                    <th scope="col">Especialidad</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Hora</th>
                                    <th scope="col">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($citas as $cita)
                                    <tr>
                                        <td><span class="admin-referencia">{{ $cita->reference }}</span></td>
                                        <td>{{ $cita->doctor->full_name }}</td>
                                        <td><span class="admin-tabla__secundario">{{ $cita->specialty->name }}</span></td>
                                        <td><span class="admin-tabla__fecha">{{ $cita->scheduled_at->format('d/m/Y') }}</span></td>
                                        <td><span class="admin-hora">{{ $cita->scheduled_at->format('H:i') }}</span></td>
                                        <td><x-estado-cita :estado="$cita->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('administrativo.partials.paginacion', ['paginator' => $citas])
                @endif
            </section>
        </div>
    </div>
@endsection
