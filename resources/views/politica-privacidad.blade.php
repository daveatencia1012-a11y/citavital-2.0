@extends('layouts.publico')

@section('titulo', 'Política de privacidad demostrativa | CitaVital')
@section('descripcion', 'Política de privacidad demostrativa de CitaVital, un prototipo académico para la gestión de citas médicas.')

@push('estilos')
    @vite('resources/css/legal.css')
@endpush

@section('contenido')
    <section class="pagina-heroe pagina-heroe--simple" aria-labelledby="titulo-pagina">
        <div class="contenedor pagina-heroe__contenido">
            <nav class="miga" aria-label="Ruta de navegación">
                <a href="{{ route('inicio') }}">Inicio</a>
                <x-icono nombre="flecha-derecha" :tamano="14" />
                <span aria-current="page">Política de privacidad</span>
            </nav>

            <p class="etiqueta etiqueta--turquesa">
                <x-icono nombre="escudo" :tamano="16" />
                Documento demostrativo
            </p>

            <h1 id="titulo-pagina">Política de privacidad demostrativa</h1>
            <p class="pagina-heroe__resumen">
                Este documento hace parte de un prototipo académico. No constituye una política
                legal definitiva ni describe el tratamiento de datos de una entidad de salud real.
            </p>
        </div>
    </section>

    <section class="seccion seccion--blanca">
        <div class="contenedor contenedor--estrecho">
            <article class="documento-legal">
                <div class="aviso aviso--advertencia">
                    <span class="aviso__icono"><x-icono nombre="alerta" :tamano="20" /></span>
                    <p>
                        <strong>Aviso académico.</strong> CitaVital no representa a una EPS o IPS real,
                        no presta servicios médicos y no debe utilizarse con información personal o
                        clínica verdadera.
                    </p>
                </div>

                <h2>1. Responsable del prototipo</h2>
                <p>
                    CitaVital es un proyecto universitario desarrollado con fines exclusivamente
                    académicos. Los datos de contacto publicados en este sitio son ficticios.
                    Correo demostrativo: <strong>contacto@citavital.demo</strong>.
                </p>

                <h2>2. Información utilizada en la demostración</h2>
                <p>
                    El prototipo contempla información de identificación básica, datos de contacto,
                    especialidades, médicos, disponibilidad y citas. Todos los registros utilizados
                    durante las pruebas deben ser ficticios y no deben corresponder a personas reales.
                </p>

                <h2>3. Finalidad</h2>
                <p>
                    La información se utiliza únicamente para demostrar el funcionamiento de la
                    solicitud, consulta y seguimiento de citas médicas dentro del proyecto académico.
                    No se realiza perfilamiento comercial ni se comparten datos con terceros.
                </p>

                <h2>4. Acceso y seguridad</h2>
                <p>
                    El diseño de la aplicación contempla autenticación, control de acceso por perfiles,
                    protección contra solicitudes falsificadas, validación de datos y registro de
                    operaciones administrativas relevantes. Estas medidas se implementarán y probarán
                    progresivamente durante el desarrollo.
                </p>

                <h2>5. Conservación y eliminación</h2>
                <p>
                    Al tratarse de una demostración, la información puede eliminarse o regenerarse
                    cuando sea necesario. No se conservarán datos reales de pacientes en ningún
                    entorno del prototipo.
                </p>

                <h2>6. Derechos de las personas</h2>
                <p>
                    En un sistema real, las personas podrían solicitar información sobre sus datos,
                    actualizarlos o pedir su eliminación conforme a la normativa aplicable. Para este
                    prototipo, cualquier consulta debe dirigirse al responsable académico del proyecto.
                </p>

                <h2>7. Vigencia</h2>
                <p>
                    Este documento es demostrativo y puede cambiar cuando el proyecto avance hacia
                    nuevas fases. La fecha de referencia es {{ date('Y') }}.
                </p>

                <div class="documento-legal__acciones">
                    <a class="boton boton--primario" href="{{ route('inicio') }}">
                        <x-icono nombre="flecha-izquierda" :tamano="17" />
                        Volver al inicio
                    </a>
                    <a class="boton boton--secundario" href="{{ route('quienes-somos') }}#aviso">
                        Leer el aviso institucional
                    </a>
                </div>
            </article>
        </div>
    </section>
@endsection
