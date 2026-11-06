<?php

namespace App\Http\Controllers\Administrativo;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Administrativo\Concerns\ProvidesAdministrativeLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\ListarCitasRequest;
use App\Http\Requests\Administrativo\OperacionCitaRequest;
use App\Http\Requests\Administrativo\ReprogramarCitaRequest;
use App\Models\AdministrativeAudit;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CitaController extends Controller
{
    use ProvidesAdministrativeLayout;

    public function index(ListarCitasRequest $request): View
    {
        $filters = $request->validated();
        $query = Appointment::query()->with(['patient', 'doctor', 'specialty']);
        if ($search = $filters['buscar'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])->orWhere('document_number', 'like', "%{$search}%"));
            });
        }
        if ($name = $filters['paciente'] ?? null) {
            $query->whereHas('patient', fn ($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$name}%"]));
        }
        if ($document = $filters['cedula'] ?? null) {
            $query->whereHas('patient', fn ($q) => $q->where('document_number', 'like', "%{$document}%"));
        }
        foreach (['medico' => 'doctor_id', 'especialidad' => 'specialty_id', 'estado' => 'status'] as $key => $column) {
            if ($value = $filters[$key] ?? null) {
                $query->where($column, $value);
            }
        }
        if ($date = $filters['fecha'] ?? null) {
            $query->whereDate('scheduled_at', $date);
        }

        return view('administrativo.citas.index', [
            ...$this->layoutData($request),
            'citas' => $query->orderBy($filters['orden'] ?? 'scheduled_at', $filters['direccion'] ?? 'asc')->orderBy('id')->paginate(15)->withQueryString(),
            'medicos' => Doctor::query()->orderBy('last_name')->get(),
            'especialidades' => Specialty::query()->orderBy('name')->get(),
            'estados' => AppointmentStatus::cases(),
        ]);
    }

    public function show(OperacionCitaRequest $request, Appointment $appointment): View
    {
        return view('administrativo.citas.show', [
            ...$this->layoutData($request),
            'cita' => $appointment->load(['patient', 'doctor', 'specialty', 'followUps.managedBy', 'rescheduledFrom', 'rescheduledTo']),
            'auditorias' => AdministrativeAudit::query()->with('actor')->where('entity_type', Appointment::class)->where('entity_id', $appointment->id)->orderByDesc('id')->get(),
        ]);
    }

    public function complete(OperacionCitaRequest $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $service->complete($appointment, $request->user('web'), $request->validated('notes'));

        return back()->with('status', 'Asistencia registrada: cita realizada.');
    }

    public function missed(OperacionCitaRequest $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $service->markMissed($appointment, $request->user('web'), $request->validated('notes'));

        return back()->with('status', 'Perdida – llamar al paciente');
    }

    public function cancel(OperacionCitaRequest $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $service->cancel($appointment, $request->validated('notes'), $request->user('web'));

        return back()->with('status', 'Cita cancelada. Se conserva el historial.');
    }

    public function edit(ReprogramarCitaRequest $request, Appointment $appointment, AvailabilityService $availability): View
    {
        $date = $request->validated('fecha');

        return view('administrativo.citas.reprogramar', [
            ...$this->layoutData($request),
            'cita' => $appointment->load(['patient', 'doctor', 'specialty']),
            'fecha' => $date,
            'turnos' => $date ? $availability->availableSlotsForDoctorOnDate($appointment->doctor, CarbonImmutable::parse($date)) : collect(),
        ]);
    }

    public function availability(ReprogramarCitaRequest $request, Appointment $appointment, AvailabilityService $availability): RedirectResponse
    {
        $date = CarbonImmutable::parse($request->validated('fecha'));
        $availability->generateSlotsForDoctor($appointment->doctor, $date, $date);

        return redirect()->route('administrativo.citas.edit', ['appointment' => $appointment, 'fecha' => $request->validated('fecha')]);
    }

    public function reschedule(ReprogramarCitaRequest $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $slot = AppointmentSlot::query()->findOrFail($request->validated('slot_id'));
        if ($slot->starts_at->toDateString() !== $request->validated('fecha')) {
            throw ValidationException::withMessages(['slot_id' => 'El turno no corresponde a la fecha elegida.']);
        }
        $new = $service->reschedule($appointment, $slot, $request->user('web'), $request->validated('notes'));

        return redirect()->route('administrativo.citas.show', $new)->with('status', 'Nueva programación registrada: '.$new->reference);
    }
}
