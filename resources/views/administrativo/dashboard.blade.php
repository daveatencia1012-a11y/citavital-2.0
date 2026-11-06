@extends('layouts.administrativo')

@section('titulo', 'Dashboard')
@section('descripcion', 'Resumen operativo del panel administrativo de CitaVital.')

@section('contenido')
    <div class="admin-contenido">
        <section class="admin-pagina" aria-labelledby="titulo-resumen">
            <div class="admin-pagina__cabecera">
                <div>
                    <p class="admin-pagina__sobre">Vista general</p>
                    <h2 id="titulo-resumen">Bienvenido, {{ $administrador->name }}</h2>
                    <p>Este es el resumen operativo de CitaVital para hoy.</p>
                </div>
                <span class="admin-pagina__sello">
                    <x-icono nombre="pulso" :tamano="17" />
                    Datos actualizados
                </span>
            </div>

            <div class="admin-indicadores" aria-label="Indicadores del sistema">
                <x-tarjeta-indicador
                    clave="patients"
                    etiqueta="Pacientes registrados"
                    :valor="$metrics['patients']"
                    icono="usuarios"
                    tono="petroleo"
                    detalle="Total en el sistema"
                />

                <x-tarjeta-indicador
                    clave="activeDoctors"
                    etiqueta="Médicos activos"
                    :valor="$metrics['activeDoctors']"
                    icono="estetoscopio"
                    tono="turquesa"
                    detalle="Disponibles para atención"
                />

                <x-tarjeta-indicador
                    clave="scheduledAppointments"
                    etiqueta="Citas programadas"
                    :valor="$metrics['scheduledAppointments']"
                    icono="agenda"
                    tono="azul"
                    detalle="Pendientes de atención"
                />

                <x-tarjeta-indicador
                    clave="completedAppointments"
                    etiqueta="Citas realizadas"
                    :valor="$metrics['completedAppointments']"
                    icono="check-circulo"
                    tono="verde"
                    detalle="Atenciones completadas"
                />

                <x-tarjeta-indicador
                    clave="canceledAppointments"
                    etiqueta="Citas canceladas"
                    :valor="$metrics['canceledAppointments']"
                    icono="cerrar"
                    tono="rojo"
                    detalle="Cancelaciones registradas"
                />

                <x-tarjeta-indicador
                    clave="missedAppointments"
                    etiqueta="Citas con inasistencia"
                    :valor="$metrics['missedAppointments']"
                    icono="alerta"
                    tono="ambar"
                    detalle="Requieren seguimiento"
                />

                <x-tarjeta-indicador
                    clave="todayAppointments"
                    etiqueta="Citas del día"
                    :valor="$metrics['todayAppointments']"
                    icono="calendario"
                    tono="menta"
                    detalle="Agenda de la fecha actual"
                />
            </div>
        </section>

        <div class="admin-paneles">
            <section class="admin-panel admin-panel--agenda" aria-labelledby="titulo-citas-dia">
                <header class="admin-panel__cabecera">
                    <div>
                        <p class="admin-panel__sobre">Agenda de hoy</p>
                        <h3 id="titulo-citas-dia">Citas del día</h3>
                        <p>Consulta las citas registradas para la fecha actual, ordenadas por horario.</p>
                    </div>
                    <span class="admin-panel__contador">
                        {{ $metrics['todayAppointments'] }} {{ $metrics['todayAppointments'] === 1 ? 'cita' : 'citas' }}
                    </span>
                </header>

                @if ($todayAppointments->isEmpty())
                    <div class="admin-vacio">
                        <span class="admin-vacio__icono">
                            <x-icono nombre="calendario" :tamano="26" />
                        </span>
                        <h4>No hay citas programadas para hoy.</h4>
                        <p>Cuando se registren citas para la fecha actual, aparecerán aquí ordenadas por horario.</p>
                    </div>
                @else
                    <div class="admin-tabla-contenedor">
                        <table class="admin-tabla">
                            <caption class="visualmente-oculto">Citas registradas para hoy</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Referencia</th>
                                    <th scope="col">Paciente</th>
                                    <th scope="col">Médico</th>
                                    <th scope="col">Especialidad</th>
                                    <th scope="col">Hora</th>
                                    <th scope="col">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($todayAppointments as $cita)
                                    <tr>
                                        <td><span class="admin-referencia">{{ $cita->reference }}</span></td>
                                        <td><span class="admin-tabla__paciente">{{ $cita->patient->full_name }}</span></td>
                                        <td>{{ $cita->doctor->full_name }}</td>
                                        <td><span class="admin-tabla__secundario">{{ $cita->specialty->name }}</span></td>
                                        <td><span class="admin-hora">{{ $cita->scheduled_at->format('H:i') }}</span></td>
                                        <td><x-estado-cita :estado="$cita->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <aside class="admin-panel admin-panel--seguimientos" aria-labelledby="titulo-seguimientos">
                <header class="admin-panel__cabecera">
                    <div>
                        <p class="admin-panel__sobre">Control</p>
                        <h3 id="titulo-seguimientos">Inasistencias por llamar</h3>
                        <p>Citas con estado NO_ASISTIO y seguimiento pendiente.</p>
                    </div>
                    <span class="admin-panel__contador admin-panel__contador--alerta">
                        {{ $pendingFollowUps->count() }} {{ $pendingFollowUps->count() === 1 ? 'pendiente' : 'pendientes' }}
                    </span>
                </header>

                @if ($pendingFollowUps->isEmpty())
                    <div class="admin-vacio admin-vacio--compacto">
                        <span class="admin-vacio__icono admin-vacio__icono--exito">
                            <x-icono nombre="check-circulo" :tamano="24" />
                        </span>
                        <h4>No hay seguimientos pendientes.</h4>
                        <p>Las inasistencias que requieran llamada aparecerán en esta sección.</p>
                    </div>
                @else
                    <ul class="admin-seguimientos">
                        @foreach ($pendingFollowUps as $seguimiento)
                            <li class="admin-seguimiento">
                                <div class="admin-seguimiento__top">
                                    <span class="admin-referencia">{{ $seguimiento->appointment->reference }}</span>
                                    <span class="admin-estado admin-estado--pendiente">Llamada pendiente</span>
                                </div>

                                <p class="admin-seguimiento__paciente">{{ $seguimiento->appointment->patient->full_name }}</p>

                                <dl class="admin-seguimiento__datos">
                                    <div>
                                        <dt>Fecha de cita</dt>
                                        <dd>{{ $seguimiento->appointment->scheduled_at->format('d/m/Y · H:i') }}</dd>
                                    </div>
                                    <div>
                                        <dt>Médico</dt>
                                        <dd>{{ $seguimiento->appointment->doctor->full_name }}</dd>
                                    </div>
                                </dl>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>
        </div>
    </div>
@endsection
