<?php

namespace App\Http\Controllers\Administrativo;

use App\Enums\SlotStatus;
use App\Enums\Weekday;
use App\Http\Controllers\Administrativo\Concerns\ProvidesAdministrativeLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\GenerarTurnosRequest;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HorarioController extends Controller
{
    use ProvidesAdministrativeLayout;

    public function __construct(private readonly AvailabilityService $availabilityService) {}

    public function index(Request $request): View
    {
        $ahora = CarbonImmutable::now(config('app.timezone'));

        $desde = $this->parseDate($request->query('desde')) ?? $ahora->startOfDay();
        $hasta = $this->parseDate($request->query('hasta')) ?? $desde->addDays(7);

        if ($hasta->lt($desde)) {
            $hasta = $desde;
        }

        if ($desde->diffInDays($hasta) > 31) {
            $hasta = $desde->addDays(31);
        }

        $doctores = Doctor::query()
            ->where('is_active', true)
            ->with('specialties:id,name,is_active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $doctorSeleccionado = null;
        $doctorId = $request->integer('doctor');

        if ($doctorId > 0) {
            $doctorSeleccionado = $doctores->firstWhere('id', $doctorId);
        }

        $doctorSeleccionado ??= $doctores->first();

        $disponibilidades = collect();
        $disponibilidadesActivas = collect();
        $bloqueos = collect();
        $bloqueosAgenda = collect();
        $turnos = collect();
        $turnosPorDia = collect();
        $citasFuturasPorDisponibilidad = collect();

        if ($doctorSeleccionado !== null) {
            $disponibilidades = Availability::query()
                ->where('doctor_id', $doctorSeleccionado->getKey())
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get();

            $disponibilidadesActivas = $disponibilidades->where('is_active', true);

            $bloqueos = AvailabilityBlock::query()
                ->where('doctor_id', $doctorSeleccionado->getKey())
                ->orderByDesc('starts_at')
                ->limit(50)
                ->get();

            $bloqueosAgenda = AvailabilityBlock::query()
                ->where('doctor_id', $doctorSeleccionado->getKey())
                ->where('is_active', true)
                ->where('starts_at', '<', $hasta->endOfDay())
                ->where('ends_at', '>', $desde->startOfDay())
                ->orderBy('starts_at')
                ->get();

            $turnos = AppointmentSlot::query()
                ->where('doctor_id', $doctorSeleccionado->getKey())
                ->whereBetween('starts_at', [$desde->startOfDay(), $hasta->endOfDay()])
                ->with([
                    'appointment.patient:id,first_name,last_name',
                    'appointment.specialty:id,name',
                ])
                ->orderBy('starts_at')
                ->get()
                ->map(function (AppointmentSlot $slot) use ($bloqueosAgenda, $disponibilidadesActivas, $ahora): AppointmentSlot {
                    $slot->setAttribute(
                        'estado_visual',
                        $this->slotVisualState($slot, $bloqueosAgenda, $disponibilidadesActivas, $ahora),
                    );

                    return $slot;
                });

            $turnosPorDia = $turnos->groupBy(
                fn (AppointmentSlot $slot): string => $slot->starts_at->toDateString()
            );

            $citasFuturasPorDisponibilidad = $disponibilidades
                ->mapWithKeys(fn (Availability $availability): array => [
                    $availability->getKey() => $this->availabilityService
                        ->futureAppointmentsCoveredByAvailability($availability)
                        ->count(),
                ]);
        }

        return view('administrativo.horarios.index', array_merge($this->layoutData($request), [
            'doctores' => $doctores,
            'doctorSeleccionado' => $doctorSeleccionado,
            'disponibilidades' => $disponibilidades,
            'bloqueos' => $bloqueos,
            'bloqueosAgenda' => $bloqueosAgenda,
            'turnos' => $turnos,
            'turnosPorDia' => $turnosPorDia,
            'citasFuturasPorDisponibilidad' => $citasFuturasPorDisponibilidad,
            'desde' => $desde,
            'hasta' => $hasta,
            'diasSemana' => Weekday::cases(),
            'tiposBloqueo' => $this->tiposBloqueo(),
            'hoy' => $ahora->toDateString(),
        ]));
    }

    public function generate(GenerarTurnosRequest $request): RedirectResponse
    {
        $doctor = Doctor::query()
            ->whereKey($request->integer('doctor_id'))
            ->where('is_active', true)
            ->firstOrFail();

        $desde = CarbonImmutable::parse((string) $request->validated('desde'), config('app.timezone'))->startOfDay();
        $hasta = CarbonImmutable::parse((string) $request->validated('hasta'), config('app.timezone'))->startOfDay();

        $franjasActivas = $doctor->availabilities()->where('is_active', true)->count();

        if ($franjasActivas === 0) {
            return redirect()
                ->route('administrativo.horarios.index', [
                    'doctor' => $doctor->getKey(),
                    'desde' => $desde->toDateString(),
                    'hasta' => $hasta->toDateString(),
                ])
                ->with('warning', 'El médico no tiene franjas de disponibilidad activas para generar turnos.');
        }

        $antes = AppointmentSlot::query()
            ->where('doctor_id', $doctor->getKey())
            ->whereBetween('starts_at', [$desde->startOfDay(), $hasta->endOfDay()])
            ->count();

        $this->availabilityService->generateSlotsForDoctor($doctor, $desde, $hasta);

        $despues = AppointmentSlot::query()
            ->where('doctor_id', $doctor->getKey())
            ->whereBetween('starts_at', [$desde->startOfDay(), $hasta->endOfDay()])
            ->count();

        $creados = max(0, $despues - $antes);

        $mensaje = $creados === 0
            ? 'No se crearon turnos nuevos: la agenda ya estaba generada para ese rango.'
            : "Generación completada. Se crearon {$creados} "
                .($creados === 1 ? 'turno nuevo' : 'turnos nuevos')
                .' en el rango seleccionado.';

        return redirect()
            ->route('administrativo.horarios.index', [
                'doctor' => $doctor->getKey(),
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
            ])
            ->with('status', $mensaje);
    }

    private function parseDate(mixed $valor): ?CarbonImmutable
    {
        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($valor, config('app.timezone'))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  Collection<int, AvailabilityBlock>  $bloqueos
     * @param  Collection<int, Availability>  $disponibilidadesActivas
     */
    private function slotVisualState(
        AppointmentSlot $slot,
        Collection $bloqueos,
        Collection $disponibilidadesActivas,
        CarbonImmutable $ahora,
    ): string {
        if ($slot->status === SlotStatus::RESERVED) {
            return 'reservado';
        }

        $bloqueado = $bloqueos->contains(
            fn (AvailabilityBlock $block): bool => $block->starts_at->lt($slot->ends_at)
                && $block->ends_at->gt($slot->starts_at)
        );

        if ($bloqueado) {
            return 'bloqueado';
        }

        if ($slot->starts_at->lte($ahora)) {
            return 'pasado';
        }

        if ($slot->status !== SlotStatus::AVAILABLE) {
            return 'no_disponible';
        }

        if (! $this->availabilityService->slotMatchesAvailability($slot, $disponibilidadesActivas)) {
            return 'no_disponible';
        }

        return 'disponible';
    }

    /**
     * @return array<string, string>
     */
    private function tiposBloqueo(): array
    {
        return [
            'descanso' => 'Descanso',
            'ausencia' => 'Ausencia',
            'vacaciones' => 'Vacaciones',
            'bloqueo' => 'Bloqueo por fecha',
        ];
    }
}
