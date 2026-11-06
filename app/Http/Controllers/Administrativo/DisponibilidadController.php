<?php

namespace App\Http\Controllers\Administrativo;

use App\Enums\Weekday;
use App\Http\Controllers\Administrativo\Concerns\ProvidesAdministrativeLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\StoreDisponibilidadRequest;
use App\Http\Requests\Administrativo\UpdateDisponibilidadRequest;
use App\Models\Availability;
use App\Models\Doctor;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisponibilidadController extends Controller
{
    use ProvidesAdministrativeLayout;

    public function __construct(private readonly AvailabilityService $availabilityService) {}

    public function store(StoreDisponibilidadRequest $request): RedirectResponse
    {
        $doctor = Doctor::query()
            ->whereKey($request->integer('doctor_id'))
            ->where('is_active', true)
            ->firstOrFail();

        $this->availabilityService->createAvailability(
            $doctor,
            $request->integer('weekday'),
            (string) $request->input('start_time'),
            (string) $request->input('end_time'),
            $request->integer('appointment_duration'),
            $request->boolean('is_active', true),
        );

        return redirect()
            ->route('administrativo.horarios.index', ['doctor' => $doctor->getKey()])
            ->with('status', 'Franja de disponibilidad registrada correctamente.');
    }

    public function edit(Request $request, Availability $availability): View
    {
        $availability->load('doctor.specialties:id,name');

        $citasFuturas = $this->availabilityService
            ->futureAppointmentsCoveredByAvailability($availability);

        return view('administrativo.horarios.edit', array_merge($this->layoutData($request), [
            'disponibilidad' => $availability,
            'doctor' => $availability->doctor,
            'citasFuturas' => $citasFuturas,
            'diasSemana' => Weekday::cases(),
        ]));
    }

    public function update(UpdateDisponibilidadRequest $request, Availability $availability): RedirectResponse
    {
        $this->availabilityService->updateAvailability(
            $availability,
            $request->integer('weekday'),
            (string) $request->input('start_time'),
            (string) $request->input('end_time'),
            $request->integer('appointment_duration'),
            $request->boolean('is_active', $availability->is_active),
        );

        return redirect()
            ->route('administrativo.horarios.index', ['doctor' => $availability->doctor_id])
            ->with('status', 'Franja de disponibilidad actualizada correctamente.');
    }

    public function toggleState(Availability $availability): RedirectResponse
    {
        $nuevoEstado = ! $availability->is_active;

        $this->availabilityService->setAvailabilityActive($availability, $nuevoEstado);

        return back()->with(
            'status',
            $nuevoEstado
                ? 'Franja de disponibilidad reactivada correctamente.'
                : 'Franja de disponibilidad desactivada correctamente.'
        );
    }

    public function destroy(Availability $availability): RedirectResponse
    {
        $doctorId = $availability->doctor_id;

        $this->availabilityService->deleteAvailability($availability);

        return redirect()
            ->route('administrativo.horarios.index', ['doctor' => $doctorId])
            ->with('status', 'Franja de disponibilidad eliminada correctamente.');
    }
}
