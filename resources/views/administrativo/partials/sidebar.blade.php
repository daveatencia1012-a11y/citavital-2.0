@php
    $grupos = [
        'Principal' => [
            [
                'nombre' => 'Dashboard',
                'icono' => 'grafico',
                'ruta' => 'administrativo.dashboard',
                'patron' => 'administrativo.dashboard',
            ],
        ],
        'Gestión' => [
            [
                'nombre' => 'Pacientes',
                'icono' => 'usuarios',
                'ruta' => 'administrativo.pacientes.index',
                'patron' => 'administrativo.pacientes.*',
            ],
            [
                'nombre' => 'Médicos',
                'icono' => 'estetoscopio',
                'ruta' => 'administrativo.medicos.index',
                'patron' => 'administrativo.medicos.*',
            ],
            [
                'nombre' => 'Especialidades',
                'icono' => 'institucion',
                'ruta' => 'administrativo.especialidades.index',
                'patron' => 'administrativo.especialidades.*',
            ],
            [
                'nombre' => 'Horarios',
                'icono' => 'reloj',
                'ruta' => 'administrativo.horarios.index',
                'patron' => 'administrativo.horarios.*',
            ],
            ['nombre' => 'Citas', 'icono' => 'agenda', 'ruta' => 'administrativo.citas.index', 'patron' => 'administrativo.citas.*'],
        ],
        'Control' => [
            ['nombre' => 'Seguimiento de inasistencias', 'icono' => 'telefono', 'ruta' => 'administrativo.seguimientos.index', 'patron' => 'administrativo.seguimientos.*'],
            ['nombre' => 'Estadísticas', 'icono' => 'pulso', 'ruta' => 'administrativo.estadisticas.index', 'patron' => 'administrativo.estadisticas.*'],
        ],
    ];
@endphp

<aside class="admin-sidebar" id="admin-sidebar" data-admin-sidebar aria-label="Navegación administrativa">
    <div class="admin-sidebar__cabecera">
        <a class="admin-sidebar__marca" href="{{ route('administrativo.dashboard') }}" aria-label="CitaVital, ir al dashboard administrativo">
            <span class="admin-sidebar__logo">
                <x-logo-citavital :tamano="36" />
            </span>
            <span class="admin-sidebar__marca-texto">
                <strong>CitaVital</strong>
                <small>Panel administrativo</small>
            </span>
        </a>

        <button class="admin-sidebar__cerrar" type="button" aria-label="Cerrar menú de navegación" data-admin-menu-close>
            <x-icono nombre="cerrar" :tamano="20" />
        </button>
    </div>

    <nav class="admin-nav" aria-label="Módulos administrativos">
        @foreach ($grupos as $grupo => $modulos)
            <div class="admin-nav__grupo">
                <p class="admin-nav__titulo">{{ $grupo }}</p>

                @foreach ($modulos as $modulo)
                    @if ($modulo['ruta'] ?? null)
                        @php($activo = request()->routeIs($modulo['patron']))
                        <a
                            class="admin-nav__enlace {{ $activo ? 'admin-nav__enlace--activo' : '' }}"
                            href="{{ route($modulo['ruta']) }}"
                            @if ($activo) aria-current="page" @endif
                        >
                            <span class="admin-nav__icono"><x-icono :nombre="$modulo['icono']" :tamano="19" /></span>
                            <span>{{ $modulo['nombre'] }}</span>
                        </a>
                    @else
                        <span class="admin-nav__enlace admin-nav__enlace--pendiente" aria-disabled="true" title="Disponible en una próxima fase">
                            <span class="admin-nav__icono"><x-icono :nombre="$modulo['icono']" :tamano="19" /></span>
                            <span>{{ $modulo['nombre'] }}</span>
                            <span class="admin-nav__pendiente">Próximamente</span>
                        </span>
                    @endif
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="admin-sidebar__nota">
        <x-icono nombre="escudo" :tamano="18" />
        <p>Acceso exclusivo para personal autorizado. Los módulos marcados como <strong>Próximamente</strong> se habilitarán en fases posteriores.</p>
    </div>
</aside>
