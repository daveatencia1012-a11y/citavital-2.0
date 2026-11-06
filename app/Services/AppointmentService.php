<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\SlotStatus;
use App\Models\AdministrativeAudit;
use App\Models\Appointment;
use App\Models\AppointmentFollowUp;
use App\Models\AppointmentSlot;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(private readonly AvailabilityService $availabilityService) {}

    public function book(
        AppointmentSlot $slot,
        Patient $patient,
        Specialty $specialty,
        ?User $actor = null,
    ): Appointment {
        return DB::transaction(function () use ($slot, $patient, $specialty, $actor): Appointment {
            $doctor = Doctor::query()->whereKey($slot->doctor_id)->lockForUpdate()->firstOrFail();
            $slot = AppointmentSlot::query()->whereKey($slot->getKey())->lockForUpdate()->firstOrFail();

            $this->assertBookableFor($slot, $doctor, $patient, $specialty);

            $slot->update(['status' => SlotStatus::RESERVED]);

            $appointment = Appointment::query()->create([
                'patient_id' => $patient->getKey(),
                'doctor_id' => $doctor->getKey(),
                'specialty_id' => $specialty->getKey(),
                'slot_id' => $slot->getKey(),
                'scheduled_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'status' => AppointmentStatus::SCHEDULED,
            ]);

            $this->audit($appointment, 'CREATE', null, AppointmentStatus::SCHEDULED->value, $actor);

            return $appointment->load(['patient', 'doctor', 'specialty', 'slot']);
        });
    }

    public function cancel(Appointment $appointment, ?string $reason = null, ?User $actor = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $actor): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->getKey())->lockForUpdate()->firstOrFail();

            if (! $appointment->isScheduled()) {
                throw ValidationException::withMessages([
                    'appointment' => 'Solo se puede cancelar una cita programada.',
                ]);
            }

            $oldStatus = $appointment->status->value;

            if ($appointment->slot_id !== null) {
                $slot = AppointmentSlot::query()->whereKey($appointment->slot_id)->lockForUpdate()->first();

                if ($slot !== null) {
                    $slot->update(['status' => $this->releasedSlotStatus($slot)]);
                }
            }

            $appointment->update([
                'status' => AppointmentStatus::CANCELED,
                'slot_id' => null,
                'canceled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->audit($appointment, 'CANCEL', $oldStatus, AppointmentStatus::CANCELED->value, $actor, $reason);

            return $appointment->fresh(['patient', 'doctor', 'specialty', 'slot']);
        });
    }

    public function reschedule(Appointment $original, AppointmentSlot $newSlot, ?User $actor = null, ?string $notes = null): Appointment
    {
        return DB::transaction(function () use ($original, $newSlot, $actor, $notes): Appointment {
            $original = Appointment::query()->whereKey($original->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($original->status, [AppointmentStatus::SCHEDULED, AppointmentStatus::MISSED], true)
                || $original->rescheduledTo()->exists()) {
                throw ValidationException::withMessages([
                    'appointment' => 'Solo se puede reprogramar una cita programada o una inasistencia sin reprogramación previa.',
                ]);
            }

            $doctorIds = collect([$original->doctor_id, $newSlot->doctor_id])
                ->unique()
                ->sort()
                ->values();

            foreach ($doctorIds as $doctorId) {
                Doctor::query()->whereKey($doctorId)->lockForUpdate()->firstOrFail();
            }

            if ($newSlot->getKey() === $original->slot_id) {
                throw ValidationException::withMessages([
                    'slot' => 'El nuevo turno debe ser diferente al turno actual.',
                ]);
            }

            $slotIds = collect([$newSlot->getKey(), $original->slot_id])
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $lockedSlots = AppointmentSlot::query()
                ->whereIn('id', $slotIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $newSlot = $lockedSlots->get($newSlot->getKey());
            $oldSlot = $original->slot_id !== null ? $lockedSlots->get($original->slot_id) : null;

            if ($newSlot === null) {
                throw ValidationException::withMessages([
                    'slot' => 'El nuevo turno seleccionado no existe.',
                ]);
            }

            if ($newSlot->doctor_id !== $original->doctor_id) {
                throw ValidationException::withMessages([
                    'slot' => 'La reprogramación debe mantenerse con el mismo médico.',
                ]);
            }

            $this->assertBookableFor($newSlot, $original->doctor()->firstOrFail(), $original->patient()->firstOrFail(), $original->specialty()->firstOrFail());

            $oldStatus = $original->status->value;

            if ($oldSlot !== null) {
                $oldSlot->update(['status' => $this->releasedSlotStatus($oldSlot)]);
            }

            $original->update([
                'status' => $original->status === AppointmentStatus::MISSED ? AppointmentStatus::MISSED : AppointmentStatus::RESCHEDULED,
                'slot_id' => null,
            ]);

            $newSlot->update(['status' => SlotStatus::RESERVED]);

            $newAppointment = Appointment::query()->create([
                'patient_id' => $original->patient_id,
                'doctor_id' => $original->doctor_id,
                'specialty_id' => $original->specialty_id,
                'slot_id' => $newSlot->getKey(),
                'scheduled_at' => $newSlot->starts_at,
                'ends_at' => $newSlot->ends_at,
                'status' => AppointmentStatus::SCHEDULED,
                'rescheduled_from_id' => $original->getKey(),
            ]);

            $this->audit($original, 'RESCHEDULE', $oldStatus, $original->status->value, $actor, trim(($notes ?? '').' Nueva cita: '.$newAppointment->reference));
            $this->audit($newAppointment, 'RESCHEDULE_NEW', null, AppointmentStatus::SCHEDULED->value, $actor, 'Origen: '.$original->reference);

            foreach ($original->followUps()->lockForUpdate()->get() as $followUp) {
                $previous = $followUp->status->value;
                $followUp->update(['status' => FollowUpStatus::RESCHEDULED, 'managed_by_id' => $actor?->id]);
                $this->audit($original, 'FOLLOW_UP_RESCHEDULE', $previous, FollowUpStatus::RESCHEDULED->value, $actor, 'Nueva cita: '.$newAppointment->reference);
            }

            return $newAppointment->load(['patient', 'doctor', 'specialty', 'slot', 'rescheduledFrom']);
        });
    }

    public function complete(Appointment $appointment, ?User $actor = null, ?string $notes = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor, $notes): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->getKey())->lockForUpdate()->firstOrFail();

            if (! $appointment->isScheduled()) {
                throw ValidationException::withMessages([
                    'appointment' => 'Solo se puede marcar como realizada una cita programada.',
                ]);
            }

            $this->assertFinished($appointment);
            $oldStatus = $appointment->status->value;
            $appointment->update(['status' => AppointmentStatus::COMPLETED]);

            $this->audit($appointment, 'COMPLETE', $oldStatus, AppointmentStatus::COMPLETED->value, $actor, $notes);

            return $appointment->fresh(['patient', 'doctor', 'specialty', 'slot']);
        });
    }

    public function markMissed(Appointment $appointment, ?User $actor = null, ?string $notes = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor, $notes): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->getKey())->lockForUpdate()->firstOrFail();

            if (! $appointment->isScheduled()) {
                throw ValidationException::withMessages([
                    'appointment' => 'Solo se puede registrar la inasistencia de una cita programada.',
                ]);
            }

            $this->assertFinished($appointment);
            $oldStatus = $appointment->status->value;
            $appointment->update(['status' => AppointmentStatus::MISSED]);

            AppointmentFollowUp::query()->firstOrCreate(
                ['appointment_id' => $appointment->getKey()],
                [
                    'status' => FollowUpStatus::PENDING_CALL,
                    'notes' => 'Seguimiento pendiente por inasistencia.',
                ],
            );

            $this->audit($appointment, 'MARK_MISSED', $oldStatus, AppointmentStatus::MISSED->value, $actor, $notes);

            return $appointment->fresh(['patient', 'doctor', 'specialty', 'slot', 'followUps']);
        });
    }

    private function assertBookableFor(
        AppointmentSlot $slot,
        Doctor $doctor,
        Patient $patient,
        Specialty $specialty,
    ): void {
        if (! $patient->is_active) {
            throw ValidationException::withMessages([
                'patient' => 'El paciente no está activo.',
            ]);
        }

        if (! $doctor->is_active) {
            throw ValidationException::withMessages([
                'doctor' => 'El médico no está activo.',
            ]);
        }

        if (! $specialty->is_active) {
            throw ValidationException::withMessages([
                'specialty' => 'La especialidad no está activa.',
            ]);
        }

        if (! $doctor->specialties()->whereKey($specialty->getKey())->exists()) {
            throw ValidationException::withMessages([
                'specialty' => 'El médico no está asociado a la especialidad seleccionada.',
            ]);
        }

        $this->availabilityService->assertSlotIsBookable($slot);

        if ($slot->doctor_id !== $doctor->getKey()) {
            throw ValidationException::withMessages([
                'slot' => 'El turno no pertenece al médico seleccionado.',
            ]);
        }
    }

    private function releasedSlotStatus(AppointmentSlot $slot): SlotStatus
    {
        return $slot->starts_at->isFuture()
            ? SlotStatus::AVAILABLE
            : SlotStatus::RELEASED;
    }

    private function assertFinished(Appointment $appointment): void
    {
        if ($appointment->ends_at->isFuture()) {
            throw ValidationException::withMessages(['appointment' => 'Espera hasta que termine el horario de la cita para registrar asistencia o inasistencia.']);
        }
    }

    public function audit(
        Appointment $appointment,
        string $operation,
        ?string $oldStatus,
        ?string $newStatus,
        ?User $actor,
        ?string $notes = null,
    ): void {
        AdministrativeAudit::query()->create([
            'actor_id' => $actor?->getKey(),
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->getKey(),
            'operation' => $operation,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
        ]);
    }
}
