<?php

namespace App\Http\Controllers\Administrativo;

use App\Enums\FollowUpStatus;
use App\Http\Controllers\Administrativo\Concerns\ProvidesAdministrativeLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\SeguimientoRequest;
use App\Models\AppointmentFollowUp;
use App\Services\FollowUpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SeguimientoController extends Controller
{
    use ProvidesAdministrativeLayout;

    public function index(SeguimientoRequest $request): View
    {
        $estado = $request->validated('estado') ?? FollowUpStatus::PENDING_CALL->value;

        return view('administrativo.seguimientos.index', [
            ...$this->layoutData($request),
            'estado' => $estado,
            'estados' => FollowUpStatus::cases(),
            'seguimientos' => AppointmentFollowUp::query()->with(['appointment.patient', 'appointment.doctor', 'managedBy'])
                ->when($estado !== 'todos', fn ($q) => $q->where('status', $estado))
                ->orderBy('created_at')->orderBy('id')->paginate(10)->withQueryString(),
        ]);
    }

    public function update(SeguimientoRequest $request, AppointmentFollowUp $followUp, FollowUpService $service): RedirectResponse
    {
        $service->record($followUp, $request->validated('action'), $request->validated('notes'), $request->user('web'));

        return back()->with('status', 'Gestión de seguimiento registrada en auditoría.');
    }
}
