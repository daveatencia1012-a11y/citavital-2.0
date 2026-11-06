<?php

use App\Http\Controllers\Administrativo\AutenticacionController;
use App\Http\Controllers\Administrativo\BloqueoController;
use App\Http\Controllers\Administrativo\CitaController;
use App\Http\Controllers\Administrativo\DashboardController;
use App\Http\Controllers\Administrativo\DisponibilidadController;
use App\Http\Controllers\Administrativo\EspecialidadController;
use App\Http\Controllers\Administrativo\EstadisticaController;
use App\Http\Controllers\Administrativo\HorarioController;
use App\Http\Controllers\Administrativo\MedicoController;
use App\Http\Controllers\Administrativo\PacienteController;
use App\Http\Controllers\Administrativo\SeguimientoController;
use App\Http\Controllers\Paciente\AutenticacionController as AutenticacionPacienteController;
use App\Http\Controllers\Paciente\CitaController as CitaPacienteController;
use App\Http\Controllers\Paciente\DashboardController as DashboardPacienteController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'inicio')->name('inicio');

Route::view('/quienes-somos', 'quienes-somos')->name('quienes-somos');

Route::view('/politica-de-privacidad', 'politica-privacidad')->name('politica-privacidad');

Route::prefix('paciente')->name('paciente.')->group(function (): void {
    Route::get('/login', [AutenticacionPacienteController::class, 'create'])->name('login');
    Route::post('/login', [AutenticacionPacienteController::class, 'store'])->name('login.store');

    Route::middleware('auth:patient')->group(function (): void {
        Route::post('/logout', [AutenticacionPacienteController::class, 'destroy'])->name('logout');

        Route::get('/dashboard', DashboardPacienteController::class)->name('dashboard');

        Route::get('/citas', [CitaPacienteController::class, 'index'])->name('citas.index');
        Route::get('/citas/solicitar', [CitaPacienteController::class, 'create'])->name('citas.solicitar');
        Route::post('/citas', [CitaPacienteController::class, 'store'])->name('citas.store');
        Route::get('/citas/historial', [CitaPacienteController::class, 'history'])->name('citas.historial');
        Route::get('/citas/{appointment}', [CitaPacienteController::class, 'show'])->name('citas.show');
    });
});

Route::prefix('administrativo')->name('administrativo.')->group(function (): void {
    Route::middleware(['auth:web', 'admin'])->group(function (): void {
        Route::get('/estadisticas', EstadisticaController::class)->name('estadisticas.index');
        Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
        Route::get('/citas/{appointment}', [CitaController::class, 'show'])->name('citas.show');
        Route::patch('/citas/{appointment}/asistencia', [CitaController::class, 'complete'])->name('citas.asistencia');
        Route::patch('/citas/{appointment}/inasistencia', [CitaController::class, 'missed'])->name('citas.inasistencia');
        Route::patch('/citas/{appointment}/cancelar', [CitaController::class, 'cancel'])->name('citas.cancelar');
        Route::get('/citas/{appointment}/reprogramar', [CitaController::class, 'edit'])->name('citas.edit');
        Route::post('/citas/{appointment}/disponibilidad', [CitaController::class, 'availability'])->name('citas.disponibilidad');
        Route::post('/citas/{appointment}/reprogramar', [CitaController::class, 'reschedule'])->name('citas.reprogramar');
        Route::get('/seguimientos', [SeguimientoController::class, 'index'])->name('seguimientos.index');
        Route::patch('/seguimientos/{followUp}', [SeguimientoController::class, 'update'])->name('seguimientos.update');
    });
    Route::get('/login', [AutenticacionController::class, 'create'])->name('login');
    Route::post('/login', [AutenticacionController::class, 'store'])->name('login.store');

    Route::post('/logout', [AutenticacionController::class, 'destroy'])
        ->middleware(['auth:web', 'admin'])
        ->name('logout');

    Route::get('/dashboard', DashboardController::class)
        ->middleware(['auth:web', 'admin'])
        ->name('dashboard');

    Route::prefix('pacientes')
        ->name('pacientes.')
        ->middleware(['auth:web', 'admin'])
        ->group(function (): void {
            Route::get('/', [PacienteController::class, 'index'])->name('index');
            Route::get('/crear', [PacienteController::class, 'create'])->name('create');
            Route::post('/', [PacienteController::class, 'store'])->name('store');
            Route::get('/{patient}', [PacienteController::class, 'show'])->name('show');
            Route::get('/{patient}/editar', [PacienteController::class, 'edit'])->name('edit');
            Route::put('/{patient}', [PacienteController::class, 'update'])->name('update');
            Route::patch('/{patient}/estado', [PacienteController::class, 'toggleState'])->name('estado');
        });

    Route::prefix('especialidades')
        ->name('especialidades.')
        ->middleware(['auth:web', 'admin'])
        ->group(function (): void {
            Route::get('/', [EspecialidadController::class, 'index'])->name('index');
            Route::get('/crear', [EspecialidadController::class, 'create'])->name('create');
            Route::post('/', [EspecialidadController::class, 'store'])->name('store');
            Route::get('/{specialty}/editar', [EspecialidadController::class, 'edit'])->name('edit');
            Route::put('/{specialty}', [EspecialidadController::class, 'update'])->name('update');
            Route::patch('/{specialty}/estado', [EspecialidadController::class, 'toggleState'])->name('estado');
        });

    Route::prefix('medicos')
        ->name('medicos.')
        ->middleware(['auth:web', 'admin'])
        ->group(function (): void {
            Route::get('/', [MedicoController::class, 'index'])->name('index');
            Route::get('/crear', [MedicoController::class, 'create'])->name('create');
            Route::post('/', [MedicoController::class, 'store'])->name('store');
            Route::get('/{doctor}/editar', [MedicoController::class, 'edit'])->name('edit');
            Route::put('/{doctor}', [MedicoController::class, 'update'])->name('update');
            Route::patch('/{doctor}/estado', [MedicoController::class, 'toggleState'])->name('estado');
        });

    Route::prefix('horarios')
        ->name('horarios.')
        ->middleware(['auth:web', 'admin'])
        ->group(function (): void {
            Route::get('/', [HorarioController::class, 'index'])->name('index');
            Route::post('/generar', [HorarioController::class, 'generate'])->name('generar');

            Route::post('/disponibilidades', [DisponibilidadController::class, 'store'])->name('disponibilidades.store');
            Route::get('/disponibilidades/{availability}/editar', [DisponibilidadController::class, 'edit'])->name('disponibilidades.edit');
            Route::put('/disponibilidades/{availability}', [DisponibilidadController::class, 'update'])->name('disponibilidades.update');
            Route::patch('/disponibilidades/{availability}/estado', [DisponibilidadController::class, 'toggleState'])->name('disponibilidades.estado');
            Route::delete('/disponibilidades/{availability}', [DisponibilidadController::class, 'destroy'])->name('disponibilidades.destroy');

            Route::post('/bloqueos', [BloqueoController::class, 'store'])->name('bloqueos.store');
            Route::patch('/bloqueos/{block}/estado', [BloqueoController::class, 'toggleState'])->name('bloqueos.estado');
            Route::delete('/bloqueos/{block}', [BloqueoController::class, 'destroy'])->name('bloqueos.destroy');
        });
});
