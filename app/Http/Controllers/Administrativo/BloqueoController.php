<?php

namespace App\Http\Controllers\Administrativo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\StoreBloqueoRequest;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class BloqueoController extends Controller
{
    public function __construct(private readonly AvailabilityService $availabilityService) {}

    public function store(StoreBloqueoRequest $request): RedirectResponse
    {
        $doctor = Doctor::query()
            ->whereKey($request->integer('doctor_id'))
            ->where('is_active', true)
            ->firstOrFail();

        $tipo = (string) $request->validated('tipo');
        $motivo = trim((string) $request->validated('motivo'));
        $etiquetas = [
            'descanso' => 'Descanso',
            'ausencia' => 'Ausencia',
            'vacaciones' => 'Vacaciones',
            'bloqueo' => 'Bloqueo por fecha',
        ];

        $reason = $etiquetas[$tipo].($motivo !== '' ? ' · '.$motivo : '');

        $startsAt = CarbonImmutable::parse(
            (string) $request->validated('starts_at'),
            config('app.timezone'),
        );

        $endsAt = CarbonImmutable::parse(
            (string) $request->validated('ends_at'),
            config('app.timezone'),
        );

        $this->availabilityService->createBlock($doctor, $startsAt, $endsAt, $reason);

        return redirect()
            ->route('administrativo.horarios.index', ['doctor' => $doctor->getKey()])
            ->with('status', 'Bloqueo de agenda registrado correctamente.');
    }

    public function toggleState(AvailabilityBlock $block): RedirectResponse
    {
        $nuevoEstado = ! $block->is_active;

        $this->availabilityService->setBlockActive($block, $nuevoEstado);

        return back()->with(
            'status',
            $nuevoEstado
                ? 'Bloqueo activado correctamente.'
                : 'Bloqueo desactivado correctamente.'
        );
    }

    public function destroy(AvailabilityBlock $block): RedirectResponse
    {
        $doctorId = $block->doctor_id;

        $this->availabilityService->deleteBlock($block);

        return redirect()
            ->route('administrativo.horarios.index', ['doctor' => $doctorId])
            ->with('status', 'Bloqueo eliminado correctamente.');
    }
}
