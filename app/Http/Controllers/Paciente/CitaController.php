<?php

namespace App\Http\Controllers\Paciente;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Paciente\StoreCitaRequest;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CitaController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly AppointmentService $appointmentService,
    ) {}

    public function index(): View
    {
        /** @var Patient $patient */
        $patient = Auth::guard('patient')->user();

        $citas = $patient->appointments()
            ->with(['doctor:id,first_name,last_name', 'specialty:id,name'])
            ->upcoming()
            ->orderBy('scheduled_at')
            ->get();

        return view('paciente.citas.index', [
            'paciente' => $patient,
            'citas' => $citas,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Patient $patient */
        $patient = Auth::guard('patient')->user();

        $especialidades = Specialty::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        $especialidadSeleccionada = null;
        $especialidadId = $request->integer('specialty');

        if ($especialidadId > 0) {
            $especialidadSeleccionada = $especialidades->firstWhere('id', $especialidadId);
        }

        $doctores = collect();
        $doctorSeleccionado = null;

        if ($especialidadSeleccionada !== null) {
            $doctores = Doctor::query()
                ->where('is_active', true)
                ->whereHas('specialties', fn ($query) => $query->whereKey($especialidadSeleccionada->getKey()))
                ->with('specialties:id,name')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'internal_code', 'is_active']);

            $doctorId = $request->integer('doctor');

            if ($doctorId > 0) {
                $doctorSeleccionado = $doctores->firstWhere('id', $doctorId);
            }
        }

        $fechaInput = trim((string) $request->query('date', ''));
        $fechaSeleccionada = null;
        $fechaInvalida = false;

        if ($doctorSeleccionado !== null && $fechaInput !== '') {
            $fechaSeleccionada = $this->parseDate($fechaInput);

            if ($fechaSeleccionada === null || $fechaSeleccionada->lt(now(config('app.timezone'))->startOfDay())) {
                $fechaInvalida = true;
                $fechaSeleccionada = null;
            }
        }

        $turnos = collect();
        $turnoSeleccionado = null;

        if ($doctorSeleccionado !== null && $fechaSeleccionada !== null) {
            $turnos = $this->availabilityService->availableSlotsForDoctorOnDate(
                $doctorSeleccionado,
                $fechaSeleccionada,
            );

            $turnoId = $request->integer('slot');

            if ($turnoId > 0) {
                $turnoSeleccionado = $turnos->firstWhere('id', $turnoId);
            }
        }

        return view('paciente.citas.solicitar', [
            'paciente' => $patient,
            'especialidades' => $especialidades,
            'especialidadSeleccionada' => $especialidadSeleccionada,
            'doctores' => $doctores,
            'doctorSeleccionado' => $doctorSeleccionado,
            'fechaInput' => $fechaInput,
            'fechaSeleccionada' => $fechaSeleccionada,
            'fechaInvalida' => $fechaInvalida,
            'turnos' => $turnos,
            'turnoSeleccionado' => $turnoSeleccionado,
            'hoy' => now(config('app.timezone'))->toDateString(),
        ]);
    }

    public function store(StoreCitaRequest $request): RedirectResponse
    {
        /** @var Patient $patient */
        $patient = Auth::guard('patient')->user();

        $specialty = Specialty::query()
            ->whereKey($request->integer('specialty_id'))
            ->where('is_active', true)
            ->first();

        $doctor = Doctor::query()
            ->whereKey($request->integer('doctor_id'))
            ->where('is_active', true)
            ->first();

        $slot = AppointmentSlot::query()
            ->whereKey($request->integer('slot_id'))
            ->first();

        $errors = [];

        if ($specialty === null) {
            $errors['specialty_id'] = 'La especialidad seleccionada no está disponible.';
        }

        if ($doctor === null) {
            $errors['doctor_id'] = 'El médico seleccionado no está disponible.';
        }

        if ($slot === null) {
            $errors['slot_id'] = 'El horario seleccionado ya no existe. Elige otro.';
        }

        if ($slot !== null && $doctor !== null && (int) $slot->doctor_id !== (int) $doctor->getKey()) {
            $errors['slot_id'] = 'El horario seleccionado no pertenece al médico elegido.';
        }

        if ($slot !== null && $slot->starts_at->toDateString() !== (string) $request->validated('date')) {
            $errors['slot_id'] = 'El horario seleccionado no corresponde a la fecha elegida.';
        }

        if ($specialty !== null && $doctor !== null
            && ! $doctor->specialties()->whereKey($specialty->getKey())->exists()) {
            $errors['doctor_id'] = 'El médico seleccionado no atiende la especialidad elegida.';
        }

        if ($errors !== []) {
            return $this->backToWizard($request, $errors);
        }

        try {
            $appointment = $this->appointmentService->book($slot, $patient, $specialty);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            if (isset($errors['slot']) && ! isset($errors['slot_id'])) {
                $errors['slot_id'] = $errors['slot'];
                unset($errors['slot']);
            }

            return $this->backToWizard($request, $errors);
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '45000'], true)) {
                throw $exception;
            }

            return $this->backToWizard($request, [
                'slot_id' => 'El horario acaba de ser ocupado. Elige otro turno disponible.',
            ]);
        }

        return redirect()
            ->route('paciente.citas.show', $appointment)
            ->with('status', "Cita {$appointment->reference} registrada correctamente.");
    }

    public function history(Request $request): View
    {
        /** @var Patient $patient */
        $patient = Auth::guard('patient')->user();

        $estado = trim((string) $request->query('status', 'todos'));

        if ($estado !== 'todos' && AppointmentStatus::tryFrom($estado) === null) {
            $estado = 'todos';
        }

        $fechaInput = trim((string) $request->query('date', ''));
        $fecha = $fechaInput !== '' ? $this->parseDate($fechaInput) : null;

        $citas = $patient->appointments()
            ->with(['doctor:id,first_name,last_name', 'specialty:id,name', 'rescheduledFrom:id,reference'])
            ->history()
            ->when($fecha !== null, fn ($query) => $query->whereDate('scheduled_at', $fecha->toDateString()))
            ->when($estado !== 'todos', fn ($query) => $query->where('status', $estado))
            ->orderByDesc('scheduled_at')
            ->paginate(8)
            ->withQueryString();

        return view('paciente.citas.historial', [
            'paciente' => $patient,
            'citas' => $citas,
            'fecha' => $fecha?->toDateString() ?? '',
            'estado' => $estado,
            'estados' => AppointmentStatus::cases(),
        ]);
    }

    public function show(Appointment $appointment): View
    {
        /** @var Patient $patient */
        $patient = Auth::guard('patient')->user();

        abort_unless((int) $appointment->patient_id === (int) $patient->getKey(), 404);

        $appointment->load([
            'doctor:id,first_name,last_name,internal_code',
            'specialty:id,name,description',
            'rescheduledFrom:id,reference,scheduled_at',
            'rescheduledTo:id,reference,scheduled_at,status',
        ]);

        return view('paciente.citas.show', [
            'paciente' => $patient,
            'cita' => $appointment,
        ]);
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));

            if ($date === false || $date->format('Y-m-d') !== $value) {
                return null;
            }

            return $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, string|array<int, string>>  $errors
     */
    private function backToWizard(Request $request, array $errors): RedirectResponse
    {
        $parameters = array_filter([
            'specialty' => $request->integer('specialty_id') ?: null,
            'doctor' => $request->integer('doctor_id') ?: null,
            'date' => $request->input('date'),
            'slot' => $request->integer('slot_id') ?: null,
        ], fn ($value): bool => $value !== null && $value !== '');

        return redirect()
            ->route('paciente.citas.solicitar', $parameters)
            ->withErrors($errors)
            ->withInput();
    }
}
