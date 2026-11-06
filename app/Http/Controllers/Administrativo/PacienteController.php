<?php

namespace App\Http\Controllers\Administrativo;

use App\Enums\InsuranceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\StorePacienteRequest;
use App\Http\Requests\Administrativo\UpdatePacienteRequest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PacienteController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('buscar', ''));

        $pacientes = Patient::query()
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $query) use ($busqueda): void {
                    $query->where('document_number', 'like', "%{$busqueda}%")
                        ->orWhere('first_name', 'like', "%{$busqueda}%")
                        ->orWhere('last_name', 'like', "%{$busqueda}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$busqueda}%"]);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('document_number')
            ->paginate(10)
            ->withQueryString();

        return view('administrativo.pacientes.index', array_merge($this->layoutData($request), [
            'pacientes' => $pacientes,
            'busqueda' => $busqueda,
        ]));
    }

    public function create(Request $request): View
    {
        return view('administrativo.pacientes.create', array_merge($this->layoutData($request), [
            'tiposSeguro' => InsuranceType::cases(),
        ]));
    }

    public function store(StorePacienteRequest $request): RedirectResponse
    {
        $paciente = Patient::query()->create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        return redirect()
            ->route('administrativo.pacientes.show', $paciente)
            ->with('status', 'Paciente registrado correctamente.');
    }

    public function show(Request $request, Patient $patient): View
    {
        $citas = $patient->appointments()
            ->with([
                'doctor:id,first_name,last_name',
                'specialty:id,name',
            ])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('administrativo.pacientes.show', array_merge($this->layoutData($request), [
            'paciente' => $patient,
            'citas' => $citas,
        ]));
    }

    public function edit(Request $request, Patient $patient): View
    {
        return view('administrativo.pacientes.edit', array_merge($this->layoutData($request), [
            'paciente' => $patient,
            'tiposSeguro' => InsuranceType::cases(),
        ]));
    }

    public function update(UpdatePacienteRequest $request, Patient $patient): RedirectResponse
    {
        $patient->fill($request->validated());
        $patient->save();

        return redirect()
            ->route('administrativo.pacientes.show', $patient)
            ->with('status', 'Paciente actualizado correctamente.');
    }

    public function toggleState(Patient $patient): RedirectResponse
    {
        $patient->is_active = ! $patient->is_active;
        $patient->save();

        $mensaje = $patient->is_active
            ? 'Paciente reactivado correctamente.'
            : 'Paciente desactivado correctamente.';

        return back()->with('status', $mensaje);
    }

    /**
     * @return array<string, mixed>
     */
    private function layoutData(Request $request): array
    {
        $ahora = now(config('app.timezone'));

        return [
            'administrador' => $request->user(),
            'currentDate' => Str::ucfirst(
                $ahora->copy()
                    ->locale('es')
                    ->translatedFormat('l, j \d\e F \d\e Y')
            ),
            'maxBirthDate' => $ahora->format('Y-m-d'),
        ];
    }
}
