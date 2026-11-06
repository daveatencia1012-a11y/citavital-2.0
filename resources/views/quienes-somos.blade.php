@extends('layouts.publico')

@section('titulo', 'Quiénes somos | CitaVital')
@section('descripcion', 'Conoce la misión, visión, valores, servicios y beneficios de CitaVital, un prototipo académico para la gestión de citas médicas.')

@push('estilos')
    @vite('resources/css/quienes-somos.css')
@endpush

@section('contenido')
    <section class="pagina-heroe" aria-labelledby="titulo-pagina">
        <div class="contenedor pagina-heroe__contenido">
            <nav class="miga" aria-label="Ruta de navegación">
                <a href="{{ route('inicio') }}">Inicio</a>
                <x-icono nombre="flecha-derecha" :tamano="14" />
                <span aria-current="page">Quiénes somos</span>
            </nav>

            <p class="etiqueta etiqueta--turquesa">
                <x-icono nombre="institucion" :tamano="16" />
                Conoce CitaVital
            </p>

            <h1 id="titulo-pagina">
                Más que citas:
                <span class="texto-destacado">organización y tranquilidad</span>
            </h1>

            <p class="pagina-heroe__resumen">
                Somos una iniciativa académica que busca simplificar la forma en que pacientes
                y personal administrativo gestionan las solicitudes de citas médicas.
            </p>

            <div class="pagina-heroe__datos" aria-label="Enfoque de la plataforma">
                <span><x-icono nombre="calendario" :tamano="18" /> Citas organizadas</span>
                <span><x-icono nombre="historial" :tamano="18" /> Seguimiento claro</span>
                <span><x-icono nombre="escudo" :tamano="18" /> Acceso responsable</span>
            </div>
        </div>
    </section>

    <section class="seccion seccion--blanca" id="presentacion" aria-labelledby="titulo-presentacion">
        <div class="contenedor">
            <div class="nosotros__grid">
                <div class="prosa" data-revelar>
                    <p class="sobre-titulo">Presentación</p>
                    <h2 id="titulo-presentacion">Una plataforma pensada para simplificar la atención</h2>
                    <p>
                        CitaVital es un prototipo académico de gestión y seguimiento de citas médicas.
                        Su propósito es demostrar cómo una herramienta digital clara y accesible puede
                        organizar la solicitud de citas, la consulta de disponibilidad y el seguimiento
                        administrativo de cada solicitud.
                    </p>
                    <p>
                        La propuesta separa la experiencia del paciente de las tareas del personal
                        administrativo. Cada perfil encuentra la información que necesita, con
                        recorridos sencillos, mensajes comprensibles y estados visibles en todo momento.
                    </p>
                    <p>
                        El proyecto se desarrolla como ejercicio universitario y utiliza únicamente
                        información ficticia. No representa a una entidad de salud real ni sustituye
                        un sistema clínico certificado.
                    </p>
                </div>

                <aside class="tarjeta-enfoque" aria-labelledby="titulo-enfoque" data-revelar>
                    <span class="tarjeta-enfoque__icono">
                        <x-icono nombre="corazon" :tamano="28" />
                    </span>
                    <h3 id="titulo-enfoque">Nuestra razón de ser</h3>
                    <p>
                        Hacer que el proceso de agendar y consultar una cita sea más ordenado,
                        comprensible y cercano para todas las personas involucradas.
                    </p>
                    <ul class="lista-check">
                        <li><x-icono nombre="check" :tamano="18" /> Acceso claro para pacientes</li>
                        <li><x-icono nombre="check" :tamano="18" /> Gestión ordenada para administradores</li>
                        <li><x-icono nombre="check" :tamano="18" /> Trazabilidad de cada cita</li>
                    </ul>
                </aside>
            </div>
        </div>
    </section>

    <section class="seccion" id="institucion" aria-labelledby="titulo-institucion">
        <div class="contenedor">
            <div class="seccion__encabezado" data-revelar>
                <p class="sobre-titulo">Quiénes somos</p>
                <h2 id="titulo-institucion">Una institución ficticia con un objetivo real de aprendizaje</h2>
                <p>
                    CitaVital simula el funcionamiento de una institución orientada a mejorar la
                    organización de las citas médicas y el seguimiento administrativo de las solicitudes.
                    Todos sus nombres, servicios y datos son demostrativos.
                </p>
            </div>

            <div class="mision-vision">
                <article class="tarjeta-institucional" data-revelar>
                    <span class="tarjeta-institucional__icono">
                        <x-icono nombre="brindar" :tamano="30" />
                    </span>
                    <p class="sobre-titulo">Misión</p>
                    <h3>Facilitar la gestión de cada solicitud</h3>
                    <p>
                        Facilitar la gestión y el seguimiento de las solicitudes de citas médicas
                        mediante herramientas digitales accesibles, organizadas y fáciles de utilizar.
                    </p>
                </article>

                <article class="tarjeta-institucional tarjeta-institucional--turquesa" data-revelar>
                    <span class="tarjeta-institucional__icono">
                        <x-icono nombre="chispas" :tamano="30" />
                    </span>
                    <p class="sobre-titulo">Visión</p>
                    <h3>Ser una referencia en digitalización</h3>
                    <p>
                        Ser una plataforma de referencia para la digitalización de procesos
                        administrativos de atención médica, contribuyendo a una experiencia más
                        organizada para pacientes y personal institucional.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="seccion seccion--azul" id="valores" aria-labelledby="titulo-valores">
        <div class="contenedor">
            <div class="seccion__encabezado seccion__encabezado--centrado" data-revelar>
                <p class="sobre-titulo">Valores institucionales</p>
                <h2 id="titulo-valores">Principios que guían nuestra propuesta</h2>
                <p>La confianza también se construye con la forma en que se trata la información.</p>
            </div>

            <div class="valores__grid">
                <article class="valor" data-revelar>
                    <span class="valor__icono"><x-icono nombre="documento" :tamano="24" /></span>
                    <h3>Responsabilidad</h3>
                    <p>Asumimos con seriedad el uso y la organización de la información.</p>
                </article>
                <article class="valor" data-revelar>
                    <span class="valor__icono"><x-icono nombre="usuarios" :tamano="24" /></span>
                    <h3>Respeto</h3>
                    <p>Reconocemos a cada persona y cuidamos la claridad de cada interacción.</p>
                </article>
                <article class="valor" data-revelar>
                    <span class="valor__icono"><x-icono nombre="ojo" :tamano="24" /></span>
                    <h3>Transparencia</h3>
                    <p>Comunicamos el estado de las solicitudes de forma directa y comprensible.</p>
                </article>
                <article class="valor" data-revelar>
                    <span class="valor__icono"><x-icono nombre="mano-corazon" :tamano="24" /></span>
                    <h3>Compromiso</h3>
                    <p>Buscamos que cada proceso sea útil, ordenado y fácil de completar.</p>
                </article>
                <article class="valor" data-revelar>
                    <span class="valor__icono"><x-icono nombre="check-circulo" :tamano="24" /></span>
                    <h3>Calidad del servicio</h3>
                    <p>Revisamos cada detalle para ofrecer una experiencia consistente.</p>
                </article>
                <article class="valor" data-revelar>
                    <span class="valor__icono"><x-icono nombre="candado" :tamano="24" /></span>
                    <h3>Protección de la información</h3>
                    <p>Aplicamos criterios de acceso responsable y mínimo dato necesario.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="seccion seccion--blanca" id="servicios" aria-labelledby="titulo-servicios">
        <div class="contenedor">
            <div class="seccion__encabezado seccion__encabezado--centrado" data-revelar>
                <p class="sobre-titulo">Nuestros servicios</p>
                <h2 id="titulo-servicios">Funciones que hacen parte de la experiencia</h2>
                <p>Herramientas diseñadas para cubrir el recorrido completo de una cita.</p>
            </div>

            <div class="servicios__grid">
                <article class="servicio" data-revelar>
                    <span class="servicio__icono"><x-icono nombre="calendario" :tamano="26" /></span>
                    <h3>Solicitud de citas médicas</h3>
                    <p>Selección guiada de especialidad, médico, fecha y horario disponible.</p>
                </article>
                <article class="servicio" data-revelar>
                    <span class="servicio__icono"><x-icono nombre="reloj" :tamano="26" /></span>
                    <h3>Consulta de disponibilidad</h3>
                    <p>Horarios generados a partir de la configuración de cada médico.</p>
                </article>
                <article class="servicio" data-revelar>
                    <span class="servicio__icono"><x-icono nombre="historial" :tamano="26" /></span>
                    <h3>Seguimiento de citas</h3>
                    <p>Estados claros para saber qué ocurrió con cada solicitud.</p>
                </article>
                <article class="servicio" data-revelar>
                    <span class="servicio__icono"><x-icono nombre="documento" :tamano="26" /></span>
                    <h3>Información de citas asignadas</h3>
                    <p>Espacio, profesional y número de referencia en un solo lugar.</p>
                </article>
                <article class="servicio" data-revelar>
                    <span class="servicio__icono"><x-icono nombre="buscar" :tamano="26" /></span>
                    <h3>Consulta de historial</h3>
                    <p>Filtros para revisar citas anteriores por estado y fecha.</p>
                </article>
                <article class="servicio" data-revelar>
                    <span class="servicio__icono"><x-icono nombre="maletin" :tamano="26" /></span>
                    <h3>Gestión administrativa</h3>
                    <p>Administración de pacientes, médicos, disponibilidad y agenda.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="seccion" id="beneficios" aria-labelledby="titulo-beneficios">
        <div class="contenedor">
            <div class="seccion__encabezado" data-revelar>
                <p class="sobre-titulo">Beneficios</p>
                <h2 id="titulo-beneficios">Una experiencia más ordenada para todos</h2>
            </div>

            <div class="beneficios__grid">
                <article class="beneficio" data-revelar>
                    <span class="beneficio__icono"><x-icono nombre="reloj" :tamano="24" /></span>
                    <h3>Ahorro de tiempo</h3>
                    <p>Menos pasos para encontrar información y solicitar una cita.</p>
                </article>
                <article class="beneficio" data-revelar>
                    <span class="beneficio__icono"><x-icono nombre="check-circulo" :tamano="24" /></span>
                    <h3>Información clara</h3>
                    <p>Estados y mensajes comprensibles en cada etapa del proceso.</p>
                </article>
                <article class="beneficio" data-revelar>
                    <span class="beneficio__icono"><x-icono nombre="grafico" :tamano="24" /></span>
                    <h3>Mejor organización</h3>
                    <p>Agenda y disponibilidad centralizadas para el personal administrativo.</p>
                </article>
                <article class="beneficio" data-revelar>
                    <span class="beneficio__icono"><x-icono nombre="escudo" :tamano="24" /></span>
                    <h3>Acceso responsable</h3>
                    <p>Cada perfil consulta únicamente la información que le corresponde.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="seccion seccion--blanca" id="preguntas" aria-labelledby="titulo-preguntas">
        <div class="contenedor contenedor--estrecho">
            <div class="seccion__encabezado seccion__encabezado--centrado" data-revelar>
                <p class="sobre-titulo">Preguntas frecuentes</p>
                <h2 id="titulo-preguntas">Resolvemos las dudas más comunes</h2>
                <p>Respuestas generales sobre el uso previsto de la plataforma.</p>
            </div>

            <div class="faq" data-revelar>
                <details class="acordeon" open>
                    <summary>
                        <span class="acordeon__icono"><x-icono nombre="pregunta" :tamano="20" /></span>
                        <span>¿Cómo solicito una cita médica?</span>
                        <span class="acordeon__indicador" aria-hidden="true"></span>
                    </summary>
                    <div class="acordeon__contenido">
                        <p>
                            Ingresa al portal del paciente con tu número de cédula, elige una especialidad,
                            un médico disponible, la fecha y uno de los horarios habilitados. Antes de
                            confirmar, el sistema verificará que el espacio siga libre.
                        </p>
                    </div>
                </details>

                <details class="acordeon">
                    <summary>
                        <span class="acordeon__icono"><x-icono nombre="historial" :tamano="20" /></span>
                        <span>¿Cómo consulto mi historial de citas?</span>
                        <span class="acordeon__indicador" aria-hidden="true"></span>
                    </summary>
                    <div class="acordeon__contenido">
                        <p>
                            Desde el panel del paciente podrás revisar las citas anteriores y filtrarlas
                            por estado o fecha. Solo tendrás acceso a las citas asociadas a tu documento.
                        </p>
                    </div>
                </details>

                <details class="acordeon">
                    <summary>
                        <span class="acordeon__icono"><x-icono nombre="calendario" :tamano="20" /></span>
                        <span>¿Cómo sé el estado de mi cita?</span>
                        <span class="acordeon__indicador" aria-hidden="true"></span>
                    </summary>
                    <div class="acordeon__contenido">
                        <p>
                            Cada cita tendrá un estado visible: programada, realizada, cancelada,
                            no asistió o reprogramada. También incluirá un número de referencia para
                            facilitar su consulta.
                        </p>
                    </div>
                </details>

                <details class="acordeon">
                    <summary>
                        <span class="acordeon__icono"><x-icono nombre="alerta" :tamano="20" /></span>
                        <span>¿Qué pasa si no puedo asistir?</span>
                        <span class="acordeon__indicador" aria-hidden="true"></span>
                    </summary>
                    <div class="acordeon__contenido">
                        <p>
                            Debes comunicarte con el área administrativa. El personal podrá registrar
                            la novedad, cancelar o reprogramar la cita según la disponibilidad y las
                            reglas definidas para el prototipo.
                        </p>
                    </div>
                </details>

                <details class="acordeon">
                    <summary>
                        <span class="acordeon__icono"><x-icono nombre="candado" :tamano="20" /></span>
                        <span>¿Quién puede acceder al módulo administrativo?</span>
                        <span class="acordeon__indicador" aria-hidden="true"></span>
                    </summary>
                    <div class="acordeon__contenido">
                        <p>
                            Únicamente el personal administrativo autorizado. Los pacientes no pueden
                            ingresar al panel administrativo, y el acceso estará protegido con autenticación
                            y controles de sesión en las siguientes fases del desarrollo.
                        </p>
                    </div>
                </details>
            </div>
        </div>
    </section>

    <section class="seccion seccion--blanca" id="aviso" aria-labelledby="titulo-aviso">
        <div class="contenedor">
            <div class="aviso-institucional" data-revelar>
                <span class="aviso-institucional__icono">
                    <x-icono nombre="alerta" :tamano="28" />
                </span>
                <div>
                    <p class="sobre-titulo">Aviso institucional</p>
                    <h2 id="titulo-aviso">CitaVital es un prototipo académico</h2>
                    <p>
                        CitaVital es un proyecto universitario y no representa a una EPS, IPS, clínica
                        o entidad de salud real. No existe relación contractual con entidades de salud,
                        habilitación sanitaria ni prestación de servicios médicos. Los nombres,
                        especialidades, contactos y datos utilizados son ficticios y tienen fines
                        exclusivamente demostrativos. Las funciones descritas se habilitarán
                        progresivamente durante el desarrollo del prototipo.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-institucional" aria-labelledby="titulo-cta">
        <div class="contenedor">
            <div class="cta-institucional__panel" data-revelar>
                <div>
                    <p class="sobre-titulo">Da el siguiente paso</p>
                    <h2 id="titulo-cta">Organiza tu próxima cita con CitaVital</h2>
                    <p>Accede al portal del paciente o regresa al inicio para conocer los demás espacios.</p>
                </div>
                <div class="cta-institucional__acciones">
                    <a class="boton boton--primario boton--grande" href="{{ route('paciente.login') }}">
                        Acceso de pacientes
                        <x-icono nombre="flecha-derecha" :tamano="18" />
                    </a>
                    <a class="boton boton--secundario boton--grande" href="{{ route('inicio') }}">
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
