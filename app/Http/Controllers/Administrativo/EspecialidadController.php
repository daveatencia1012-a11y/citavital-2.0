<?php

namespace App\Http\Controllers\Administrativo;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Administrativo\Concerns\ProvidesAdministrativeLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\StoreEspecialidadRequest;
use App\Http\Requests\Administrativo\UpdateEspecialidadRequest;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EspecialidadController extends Controller
{
    use ProvidesAdministrativeLayout;

    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('buscar', ''));
        $estado = (string) $request->query('estado', 'todos');

        if (! in_array($estado, ['todos', 'activos', 'inactivos'], true)) {
            $estado = 'todos';
        }

        $especialidades = Specialty::query()
            ->withCount([
                'appointments as future_appointments_count' => fn (Builder $query) => $query
                    ->where('status', AppointmentStatus::SCHEDULED->value)
                    ->where('scheduled_at', '>', now()),
            ])
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $query) use ($busqueda): void {
                    $query->where('name', 'like', "%{$busqueda}%")
                        ->orWhere('description', 'like', "%{$busqueda}%");
                });
            })
            ->when($estado === 'activos', fn (Builder $query) => $query->where('is_active', true))
            ->when($estado === 'inactivos', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('administrativo.especialidades.index', array_merge($this->layoutData($request), [
            'especialidades' => $especialidades,
            'busqueda' => $busqueda,
            'estado' => $estado,
        ]));
    }

    public function create(Request $request): View
    {
        return view('administrativo.especialidades.create', $this->layoutData($request));
    }

    public function store(StoreEspecialidadRequest $request): RedirectResponse
    {
        Specialty::query()->create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('administrativo.especialidades.index')
            ->with('status', 'Especialidad registrada correctamente.');
    }

    public function edit(Request $request, Specialty $specialty): View
    {
        return view('administrativo.especialidades.edit', array_merge($this->layoutData($request), [
            'especialidad' => $specialty,
        ]));
    }

    public function update(UpdateEspecialidadRequest $request, Specialty $specialty): RedirectResponse
    {
        $specialty->fill($request->validated());
        $specialty->save();

        return redirect()
            ->route('administrativo.especialidades.index')
            ->with('status', 'Especialidad actualizada correctamente.');
    }

    public function toggleState(Specialty $specialty): RedirectResponse
    {
        $specialty->is_active = ! $specialty->is_active;
        $specialty->save();

        if ($specialty->is_active) {
            return back()->with('status', 'Especialidad reactivada correctamente.');
        }

        $citasFuturas = $this->futureAppointmentsCount($specialty);

        $mensaje = 'Especialidad desactivada correctamente.';
        $advertencia = null;

        if ($citasFuturas > 0) {
            $advertencia = "La especialidad conserva {$citasFuturas} "
                .($citasFuturas === 1 ? 'cita futura' : 'citas futuras')
                .'. No se cancelaron automáticamente.';
        }

        $redirect = back()->with('status', $mensaje);

        if ($advertencia !== null) {
            $redirect->with('warning', $advertencia);
        }

        return $redirect;
    }

    private function futureAppointmentsCount(Specialty $specialty): int
    {
        return $specialty->appointments()
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->count();
    }
}
