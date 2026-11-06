<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    public function createAvailability(
        Doctor $doctor,
        int $weekday,
        string $startTime,
        string $endTime,
        int $appointmentDuration,
        bool $isActive = true,
    ): Availability {
        $this->assertValidAvailabilityData($weekday, $startTime, $endTime, $appointmentDuration);

        return DB::transaction(function () use ($doctor, $weekday, $startTime, $endTime, $appointmentDuration, $isActive): Availability {
            Doctor::query()->whereKey($doctor->getKey())->lockForUpdate()->firstOrFail();

            $this->assertAvailabilityIsUnique($doctor, $weekday, $startTime, $endTime);

            if ($isActive) {
                $this->assertAvailabilityDoesNotOverlap($doctor, $weekday, $startTime, $endTime);
            }

            return $doctor->availabilities()->create([
                'weekday' => $weekday,
                'start_time' => $this->normalizeTime($startTime),
                'end_time' => $this->normalizeTime($endTime),
                'appointment_duration' => $appointmentDuration,
                'is_active' => $isActive,
            ]);
        });
    }

    public function updateAvailability(
        Availability $availability,
        int $weekday,
        string $startTime,
        string $endTime,
        int $appointmentDuration,
        bool $isActive,
    ): Availability {
        $this->assertValidAvailabilityData($weekday, $startTime, $endTime, $appointmentDuration);

        return DB::transaction(function () use ($availability, $weekday, $startTime, $endTime, $appointmentDuration, $isActive): Availability {
            $doctor = Doctor::query()
                ->whereKey($availability->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $availability = Availability::query()
                ->whereKey($availability->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $start = $this->normalizeTime($startTime);
            $end = $this->normalizeTime($endTime);

            $this->assertAvailabilityIsUnique($doctor, $weekday, $start, $end, $availability->getKey());

            if ($isActive) {
                $this->assertAvailabilityDoesNotOverlap(
                    $doctor,
                    $weekday,
                    $start,
                    $end,
                    $availability->getKey(),
                );
            }

            $this->assertAvailabilityChangePreservesAppointments(
                $availability,
                $weekday,
                $start,
                $end,
                $appointmentDuration,
                $isActive,
            );

            $availability->update([
                'weekday' => $weekday,
                'start_time' => $start,
                'end_time' => $end,
                'appointment_duration' => $appointmentDuration,
                'is_active' => $isActive,
            ]);

            return $availability->fresh();
        });
    }

    public function setAvailabilityActive(Availability $availability, bool $isActive): Availability
    {
        return DB::transaction(function () use ($availability, $isActive): Availability {
            Doctor::query()
                ->whereKey($availability->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $availability = Availability::query()
                ->whereKey($availability->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($isActive && ! $availability->is_active) {
                $this->assertAvailabilityDoesNotOverlap(
                    $availability->doctor,
                    $availability->weekday,
                    $availability->start_time,
                    $availability->end_time,
                    $availability->getKey(),
                );
            }

            if (! $isActive && $availability->is_active) {
                $this->assertAvailabilityChangePreservesAppointments(
                    $availability,
                    $availability->weekday,
                    $availability->start_time,
                    $availability->end_time,
                    $availability->appointment_duration,
                    false,
                );
            }

            $availability->update(['is_active' => $isActive]);

            return $availability->fresh();
        });
    }

    public function deleteAvailability(Availability $availability): void
    {
        DB::transaction(function () use ($availability): void {
            Doctor::query()
                ->whereKey($availability->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $availability = Availability::query()
                ->whereKey($availability->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($availability->is_active) {
                $this->assertAvailabilityChangePreservesAppointments(
                    $availability,
                    $availability->weekday,
                    $availability->start_time,
                    $availability->end_time,
                    $availability->appointment_duration,
                    false,
                );
            }

            $availability->delete();
        });
    }

    /**
     * Genera los turnos de una disponibilidad para un rango de fechas.
     *
     * @return Collection<int, AppointmentSlot>
     */
    public function generateSlots(Availability $availability, CarbonInterface $from, CarbonInterface $to): Collection
    {
        if (! $availability->is_active) {
            return collect();
        }

        $this->assertValidAvailabilityData(
            $availability->weekday,
            $availability->start_time,
            $availability->end_time,
            $availability->appointment_duration,
        );

        $timezone = config('app.timezone');
        $rangeStart = CarbonImmutable::parse($from->format('Y-m-d H:i:s'), $timezone)->startOfDay();
        $rangeEnd = CarbonImmutable::parse($to->format('Y-m-d H:i:s'), $timezone)->endOfDay();

        if ($rangeEnd->lt($rangeStart)) {
            throw ValidationException::withMessages([
                'range' => 'La fecha final debe ser posterior a la fecha inicial.',
            ]);
        }

        return DB::transaction(function () use ($availability, $rangeStart, $rangeEnd): Collection {
            Doctor::query()->whereKey($availability->doctor_id)->lockForUpdate()->firstOrFail();

            $slots = collect();
            $now = CarbonImmutable::now(config('app.timezone'));

            for ($date = $rangeStart; $date->lte($rangeEnd); $date = $date->addDay()) {
                if ($date->dayOfWeekIso !== $availability->weekday) {
                    continue;
                }

                $dayStart = CarbonImmutable::parse(
                    $date->toDateString().' '.$availability->start_time,
                    config('app.timezone'),
                );
                $dayEnd = CarbonImmutable::parse(
                    $date->toDateString().' '.$availability->end_time,
                    config('app.timezone'),
                );

                for ($cursor = $dayStart; $cursor->lt($dayEnd); $cursor = $cursor->addMinutes($availability->appointment_duration)) {
                    $slotEnd = $cursor->addMinutes($availability->appointment_duration);

                    if ($slotEnd->gt($dayEnd)) {
                        break;
                    }

                    if ($cursor->lte($now)) {
                        continue;
                    }

                    if ($this->isBlocked($availability->doctor_id, $cursor, $slotEnd)) {
                        continue;
                    }

                    $slot = AppointmentSlot::query()
                        ->where('doctor_id', $availability->doctor_id)
                        ->where('starts_at', $cursor)
                        ->where('ends_at', $slotEnd)
                        ->first();

                    if ($slot === null) {
                        $this->assertSlotDoesNotOverlap($availability->doctor_id, $cursor, $slotEnd);

                        $slot = AppointmentSlot::query()->create([
                            'doctor_id' => $availability->doctor_id,
                            'starts_at' => $cursor,
                            'ends_at' => $slotEnd,
                            'status' => SlotStatus::AVAILABLE,
                        ]);
                    }

                    $slots->push($slot);
                }
            }

            return $slots;
        });
    }

    /**
     * Genera de forma idempotente los turnos de todas las franjas activas del médico.
     *
     * @return Collection<int, AppointmentSlot>
     */
    public function generateSlotsForDoctor(Doctor $doctor, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return DB::transaction(function () use ($doctor, $from, $to): Collection {
            Doctor::query()->whereKey($doctor->getKey())->lockForUpdate()->firstOrFail();

            $slots = collect();

            $availabilities = $doctor->availabilities()
                ->where('is_active', true)
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get();

            foreach ($availabilities as $availability) {
                $slots = $slots->merge($this->generateSlots($availability, $from, $to));
            }

            return $slots->unique('id')->values();
        });
    }

    /**
     * Devuelve los turnos realmente reservables de un médico para un día.
     *
     * Genera de forma idempotente los turnos que falten y descarta los que no
     * corresponden a una franja activa, los bloqueados, los ocupados y los que
     * ya pasaron. El portal del paciente consume este método para no duplicar
     * la lógica de disponibilidad.
     *
     * @return Collection<int, AppointmentSlot>
     */
    public function availableSlotsForDoctorOnDate(Doctor $doctor, CarbonInterface $date): Collection
    {
        if (! $doctor->is_active) {
            return collect();
        }

        $timezone = config('app.timezone');
        $day = CarbonImmutable::parse($date->toDateString(), $timezone)->startOfDay();
        $now = CarbonImmutable::now($timezone);

        if ($day->lt($now->startOfDay())) {
            return collect();
        }

        $availabilities = Availability::query()
            ->where('doctor_id', $doctor->getKey())
            ->where('is_active', true)
            ->get();

        return AppointmentSlot::query()
            ->where('doctor_id', $doctor->getKey())
            ->whereBetween('starts_at', [$day->startOfDay(), $day->endOfDay()])
            ->where('status', SlotStatus::AVAILABLE->value)
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (AppointmentSlot $slot): bool => $this->slotMatchesAvailability($slot, $availabilities)
                && ! $this->isBlocked($slot->doctor_id, $slot->starts_at, $slot->ends_at))
            ->values();
    }

    public function createBlock(
        Doctor $doctor,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?string $reason = null,
    ): AvailabilityBlock {
        if ($endsAt->lte($startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => 'La fecha y hora final del bloqueo debe ser posterior al inicio.',
            ]);
        }

        return DB::transaction(function () use ($doctor, $startsAt, $endsAt, $reason): AvailabilityBlock {
            Doctor::query()->whereKey($doctor->getKey())->lockForUpdate()->firstOrFail();

            $this->assertBlockDoesNotAffectAppointments($doctor, $startsAt, $endsAt);

            return $doctor->availabilityBlocks()->create([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'reason' => $reason,
                'is_active' => true,
            ]);
        });
    }

    public function setBlockActive(AvailabilityBlock $block, bool $isActive): AvailabilityBlock
    {
        return DB::transaction(function () use ($block, $isActive): AvailabilityBlock {
            $doctor = Doctor::query()
                ->whereKey($block->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $block = AvailabilityBlock::query()
                ->whereKey($block->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($isActive && ! $block->is_active) {
                $this->assertBlockDoesNotAffectAppointments($doctor, $block->starts_at, $block->ends_at);
            }

            $block->update(['is_active' => $isActive]);

            return $block->fresh();
        });
    }

    public function deleteBlock(AvailabilityBlock $block): void
    {
        $block->delete();
    }

    public function assertSlotIsBookable(AppointmentSlot $slot): void
    {
        $slot = $slot->fresh();

        if ($slot === null) {
            throw ValidationException::withMessages([
                'slot' => 'El turno seleccionado no existe.',
            ]);
        }

        if ($slot->status !== SlotStatus::AVAILABLE) {
            throw ValidationException::withMessages([
                'slot' => 'El turno seleccionado ya no está disponible.',
            ]);
        }

        if ($slot->starts_at->lte(now())) {
            throw ValidationException::withMessages([
                'slot' => 'No se puede reservar un turno en el pasado.',
            ]);
        }

        if (! $slot->starts_at->isSameDay($slot->ends_at)) {
            throw ValidationException::withMessages([
                'slot' => 'El turno debe comenzar y terminar el mismo día.',
            ]);
        }

        $availabilities = Availability::query()
            ->where('doctor_id', $slot->doctor_id)
            ->where('is_active', true)
            ->get();

        if (! $this->slotMatchesAvailability($slot, $availabilities)) {
            throw ValidationException::withMessages([
                'slot' => 'El turno no corresponde a una disponibilidad activa del médico.',
            ]);
        }

        if ($this->isBlocked($slot->doctor_id, $slot->starts_at, $slot->ends_at)) {
            throw ValidationException::withMessages([
                'slot' => 'El médico tiene un bloqueo de agenda para ese horario.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Availability>  $availabilities
     */
    public function slotMatchesAvailability(AppointmentSlot $slot, Collection $availabilities): bool
    {
        if (! $slot->starts_at->isSameDay($slot->ends_at)) {
            return false;
        }

        $duration = (int) $slot->starts_at->diffInMinutes($slot->ends_at);
        $startTime = $slot->starts_at->format('H:i:s');
        $endTime = $slot->ends_at->format('H:i:s');

        return $availabilities->contains(
            fn (Availability $availability): bool => $availability->is_active
                && $availability->weekday === $slot->starts_at->dayOfWeekIso
                && $availability->appointment_duration === $duration
                && $availability->start_time <= $startTime
                && $availability->end_time >= $endTime
        );
    }

    public function assertSlotDoesNotOverlap(
        int $doctorId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $ignoreSlotId = null,
    ): void {
        if ($endsAt->lte($startsAt)) {
            throw ValidationException::withMessages([
                'slot' => 'La hora final del turno debe ser posterior a la hora inicial.',
            ]);
        }

        $overlap = AppointmentSlot::query()
            ->where('doctor_id', $doctorId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreSlotId, fn ($query) => $query->whereKeyNot($ignoreSlotId))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'slot' => 'El turno se superpone con otro turno del mismo médico.',
            ]);
        }
    }

    public function isBlocked(int $doctorId, CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        return AvailabilityBlock::query()
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }

    public function assertAvailabilityDoesNotOverlap(
        Doctor $doctor,
        int $weekday,
        string $startTime,
        string $endTime,
        ?int $ignoreAvailabilityId = null,
    ): void {
        $start = $this->normalizeTime($startTime);
        $end = $this->normalizeTime($endTime);

        $overlap = Availability::query()
            ->where('doctor_id', $doctor->getKey())
            ->where('weekday', $weekday)
            ->where('is_active', true)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignoreAvailabilityId, fn ($query) => $query->whereKeyNot($ignoreAvailabilityId))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'availability' => 'La disponibilidad se superpone con otra franja activa del mismo médico.',
            ]);
        }
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function futureAppointmentsCoveredByAvailability(Availability $availability): Collection
    {
        if (! $availability->is_active) {
            return collect();
        }

        return Appointment::query()
            ->where('doctor_id', $availability->doctor_id)
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->get()
            ->filter(fn (Appointment $appointment): bool => $this->availabilityCoversAppointment($availability, $appointment))
            ->values();
    }

    private function assertAvailabilityIsUnique(
        Doctor $doctor,
        int $weekday,
        string $startTime,
        string $endTime,
        ?int $ignoreAvailabilityId = null,
    ): void {
        $start = $this->normalizeTime($startTime);
        $end = $this->normalizeTime($endTime);

        $exists = Availability::query()
            ->where('doctor_id', $doctor->getKey())
            ->where('weekday', $weekday)
            ->where('start_time', $start)
            ->where('end_time', $end)
            ->when($ignoreAvailabilityId, fn ($query) => $query->whereKeyNot($ignoreAvailabilityId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'availability' => 'Ya existe una franja con ese día y horario para el médico.',
            ]);
        }
    }

    private function assertAvailabilityChangePreservesAppointments(
        Availability $availability,
        int $weekday,
        string $startTime,
        string $endTime,
        int $appointmentDuration,
        bool $isActive,
    ): void {
        if (! $availability->is_active) {
            return;
        }

        $affected = $this->futureAppointmentsCoveredByAvailability($availability);

        if ($affected->isEmpty()) {
            return;
        }

        $replacement = $isActive
            ? [
                'weekday' => $weekday,
                'start_time' => $this->normalizeTime($startTime),
                'end_time' => $this->normalizeTime($endTime),
                'appointment_duration' => $appointmentDuration,
            ]
            : null;

        $uncovered = $affected->filter(
            fn (Appointment $appointment): bool => ! $this->hasActiveAvailabilityCovering(
                $appointment,
                $availability->doctor_id,
                $availability->getKey(),
                $replacement,
            )
        );

        if ($uncovered->isNotEmpty()) {
            throw ValidationException::withMessages([
                'availability' => 'No se puede modificar la franja porque dejaría sin disponibilidad válida las siguientes citas futuras: '
                    .$this->formatAppointmentReferences($uncovered)
                    .'. Reprograma o cancela esas citas primero.',
            ]);
        }
    }

    /**
     * @param  array{weekday: int, start_time: string, end_time: string, appointment_duration: int}|null  $replacement
     */
    private function hasActiveAvailabilityCovering(
        Appointment $appointment,
        int $doctorId,
        int $ignoreAvailabilityId,
        ?array $replacement = null,
    ): bool {
        $candidates = Availability::query()
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->whereKeyNot($ignoreAvailabilityId)
            ->get();

        if ($replacement !== null) {
            $candidates->push(new Availability([
                'doctor_id' => $doctorId,
                'weekday' => $replacement['weekday'],
                'start_time' => $replacement['start_time'],
                'end_time' => $replacement['end_time'],
                'appointment_duration' => $replacement['appointment_duration'],
                'is_active' => true,
            ]));
        }

        return $candidates->contains(
            fn (Availability $candidate): bool => $this->availabilityCoversAppointment($candidate, $appointment)
        );
    }

    private function availabilityCoversAppointment(Availability $availability, Appointment $appointment): bool
    {
        $appointmentStart = $appointment->scheduled_at;
        $appointmentEnd = $appointment->ends_at;

        if ($appointmentStart === null || $appointmentEnd === null) {
            return false;
        }

        $duration = (int) $appointmentStart->diffInMinutes($appointmentEnd);

        return $availability->is_active
            && $availability->weekday === $appointmentStart->dayOfWeekIso
            && $availability->appointment_duration === $duration
            && $availability->start_time <= $appointmentStart->format('H:i:s')
            && $availability->end_time >= $appointmentEnd->format('H:i:s');
    }

    private function assertBlockDoesNotAffectAppointments(
        Doctor $doctor,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
    ): void {
        $conflicts = Appointment::query()
            ->where('doctor_id', $doctor->getKey())
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->orderBy('scheduled_at')
            ->get();

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'starts_at' => 'El bloqueo afecta citas futuras confirmadas: '
                    .$this->formatAppointmentReferences($conflicts)
                    .'. Reprograma o cancela esas citas antes de bloquear la agenda.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function formatAppointmentReferences(Collection $appointments): string
    {
        $references = $appointments
            ->pluck('reference')
            ->filter()
            ->take(5)
            ->implode(', ');

        $remaining = $appointments->count() - 5;

        return $remaining > 0
            ? $references.' y '.$remaining.' más'
            : $references;
    }

    private function assertValidAvailabilityData(int $weekday, string $startTime, string $endTime, int $duration): void
    {
        if ($weekday < 1 || $weekday > 7) {
            throw ValidationException::withMessages([
                'weekday' => 'El día de la semana debe estar entre 1 (lunes) y 7 (domingo).',
            ]);
        }

        if ($duration < 5 || $duration > 240) {
            throw ValidationException::withMessages([
                'appointment_duration' => 'La duración de cada cita debe estar entre 5 y 240 minutos.',
            ]);
        }

        $start = CarbonImmutable::parse($this->normalizeTime($startTime));
        $end = CarbonImmutable::parse($this->normalizeTime($endTime));

        if ($start->gte($end)) {
            throw ValidationException::withMessages([
                'availability' => 'La hora inicial debe ser anterior a la hora final.',
            ]);
        }

        if ((int) $start->diffInMinutes($end) % $duration !== 0) {
            throw ValidationException::withMessages([
                'appointment_duration' => 'La franja debe ser divisible por la duración de la cita.',
            ]);
        }
    }

    private function normalizeTime(string $time): string
    {
        return CarbonImmutable::parse($time)->format('H:i:s');
    }
}
