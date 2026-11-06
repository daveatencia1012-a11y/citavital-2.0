<?php

namespace App\Http\Controllers\Administrativo;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Administrativo\Concerns\ProvidesAdministrativeLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\StoreMedicoRequest;
use App\Http\Requests\Administrativo\UpdateMedicoRequest;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MedicoController extends Controller
{
    use ProvidesAdministrativeLayout;

    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('buscar', ''));
        $estado = (string) $request->query('estado', 'todos');
        $especialidadFiltro = $request->integer('especialidad');

        if (! in_array($estado, ['todos', 'activos', 'inactivos'], true)) {
            $estado = 'todos';
        }

        $medicos = Doctor::query()
            ->with('specialties:id,name,is_active')
            ->withCount([
                'appointments as future_appointments_count' => fn (Builder $query) => $query
                    ->where('status', AppointmentStatus::SCHEDULED->value)
                    ->where('scheduled_at', '>', now()),
            ])
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $query) use ($busqueda): void {
                    $query->where('internal_code', 'like', "%{$busqueda}%")
                        ->orWhere('first_name', 'like', "%{$busqueda}%")
                        ->orWhere('last_name', 'like', "%{$busqueda}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$busqueda}%"]);
                });
            })
            ->when($estado === 'activos', fn (Builder $query) => $query->where('is_active', true))
            ->when($estado === 'inactivos', fn (Builder $query) => $query->where('is_active', false))
            ->when($especialidadFiltro > 0, function (Builder $query) use ($especialidadFiltro): void {
                $query->whereHas('specialties', fn (Builder $query) => $query->where('specialties.id', $especialidadFiltro));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('administrativo.medicos.index', array_merge($this->layoutData($request), [
            'medicos' => $medicos,
            'especialidades' => Specialty::query()->orderBy('name')->get(['id', 'name', 'is_active']),
            'busqueda' => $busqueda,
            'estado' => $estado,
            'especialidadFiltro' => $especialidadFiltro,
        ]));
    }

    public function create(Request $request): View
    {
        return view('administrativo.medicos.create', array_merge($this->layoutData($request), [
            'especialidades' => Specialty::query()->orderBy('name')->get(['id', 'name', 'is_active']),
        ]));
    }

    public function store(StoreMedicoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $especialidades = array_map('intval', $datos['specialties']);
        unset($datos['specialties']);

        DB::transaction(function () use ($datos, $especialidades, $request): void {
            $medico = Doctor::query()->create([
                'internal_code' => $datos['internal_code'],
                'first_name' => $datos['first_name'],
                'last_name' => $datos['last_name'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            $medico->specialties()->sync($especialidades);
        });

        return redirect()
            ->route('administrativo.medicos.index')
            ->with('status', 'Médico registrado correctamente.');
    }

    public function edit(Request $request, Doctor $doctor): View
    {
        $doctor->load('specialties:id,name,is_active');

        return view('administrativo.medicos.edit', array_merge($this->layoutData($request), [
            'medico' => $doctor,
            'especialidades' => Specialty::query()->orderBy('name')->get(['id', 'name', 'is_active']),
        ]));
    }

    public function update(UpdateMedicoRequest $request, Doctor $doctor): RedirectResponse
    {
        $datos = $request->validated();
        $especialidades = array_map('intval', $datos['specialties']);
        unset($datos['specialties']);

        DB::transaction(function () use ($doctor, $datos, $especialidades): void {
            $medico = Doctor::query()
                ->whereKey($doctor->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanRemoveSpecialties($medico, $especialidades);

            $medico->update([
                'internal_code' => $datos['internal_code'],
                'first_name' => $datos['first_name'],
                'last_name' => $datos['last_name'],
            ]);

            $medico->specialties()->sync($especialidades);
        });

        return redirect()
            ->route('administrativo.medicos.index')
            ->with('status', 'Médico actualizado correctamente.');
    }

    public function toggleState(Doctor $doctor): RedirectResponse
    {
        $doctor->is_active = ! $doctor->is_active;
        $doctor->save();

        if ($doctor->is_active) {
            return back()->with('status', 'Médico reactivado correctamente.');
        }

        $citasFuturas = $this->futureAppointmentsCount($doctor);

        $redirect = back()->with('status', 'Médico desactivado correctamente.');

        if ($citasFuturas > 0) {
            $redirect->with(
                'warning',
                "El médico conserva {$citasFuturas} "
                .($citasFuturas === 1 ? 'cita futura' : 'citas futuras')
                .'. No se cancelaron automáticamente y no se permitirán nuevas reservas con él.'
            );
        }

        return $redirect;
    }

    /**
     * @param  array<int, int>  $nuevasEspecialidades
     */
    private function assertCanRemoveSpecialties(Doctor $doctor, array $nuevasEspecialidades): void
    {
        $actuales = $doctor->specialties()->pluck('specialties.id')->map(fn ($id) => (int) $id);
        $removidas = $actuales->diff($nuevasEspecialidades)->values();

        if ($removidas->isEmpty()) {
            return;
        }

        $citas = $doctor->appointments()
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->whereIn('specialty_id', $removidas->all())
            ->orderBy('scheduled_at')
            ->get(['reference', 'scheduled_at']);

        if ($citas->isEmpty()) {
            return;
        }

        $referencias = $citas->pluck('reference')->filter()->take(5)->implode(', ');
        $restantes = $citas->count() - 5;
        $sufijo = $restantes > 0 ? " y {$restantes} más" : '';

        throw ValidationException::withMessages([
            'specialties' => 'No se puede quitar una especialidad que tiene citas futuras programadas ('
                .$referencias.$sufijo
                .'). Reprograma o cancela esas citas primero.',
        ]);
    }

    private function futureAppointmentsCount(Doctor $doctor): int
    {
        return $doctor->appointments()
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->count();
    }
}
