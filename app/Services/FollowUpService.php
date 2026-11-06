<?php

namespace App\Services;

use App\Enums\FollowUpStatus;
use App\Models\Appointment;
use App\Models\AppointmentFollowUp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowUpService
{
    public function __construct(private readonly AppointmentService $appointments) {}

    public function record(AppointmentFollowUp $followUp, string $action, string $notes, User $actor): void
    {
        DB::transaction(function () use ($followUp, $action, $notes, $actor): void {
            // Mismo orden de bloqueo que la reprogramación: cita, seguimiento.
            $appointment = Appointment::query()->whereKey($followUp->appointment_id)->lockForUpdate()->firstOrFail();
            $followUp = AppointmentFollowUp::query()->whereKey($followUp->id)->lockForUpdate()->firstOrFail();
            if (in_array($followUp->status, [FollowUpStatus::CLOSED, FollowUpStatus::RESCHEDULED], true)) {
                throw ValidationException::withMessages(['action' => 'Este seguimiento ya está finalizado.']);
            }
            $previous = $followUp->status->value;
            $status = match ($action) {
                'call', 'note' => $followUp->status,
                'contact' => FollowUpStatus::CONTACTED,
                'close' => FollowUpStatus::CLOSED,
                default => throw ValidationException::withMessages(['action' => 'Acción de seguimiento no válida.']),
            };
            $followUp->update([
                'status' => $status,
                'notes' => trim(($followUp->notes ?? '')."\n".now()->format('d/m/Y H:i').' · '.$actor->name.' · '.$action.': '.$notes),
                'contacted_at' => $action === 'contact' ? now() : $followUp->contacted_at,
                'managed_by_id' => $actor->id,
            ]);
            $this->appointments->audit($appointment, 'FOLLOW_UP_'.strtoupper($action), $previous, $status->value, $actor, $notes);
        });
    }
}
