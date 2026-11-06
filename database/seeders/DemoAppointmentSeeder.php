<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentFollowUp;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class DemoAppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $availabilityService = app(AvailabilityService::class);

        Availability::query()
            ->where('is_active', true)
            ->get()
            ->each(fn (Availability $availability) => $availabilityService->generateSlots(
                $availability,
                now(),
                now()->addDays(30),
            ));

        $this->seedScheduledAppointments();
        $this->seedCanceledAppointment();
        $this->seedCompletedAppointment();
        $this->seedMissedAppointment();
        $this->seedRescheduledAppointments();
    }

    private function seedScheduledAppointments(): void
    {
        $this->createScheduledAppointment(
            'CV-DEMO-PROGRAMADA-01',
            $this->patient('10000001'),
            $this->doctor('MED-001'),
            $this->specialty('Medicina General'),
        );

        $this->createScheduledAppointment(
            'CV-DEMO-PROGRAMADA-02',
            $this->patient('10000002'),
            $this->doctor('MED-002'),
            $this->specialty('Pediatría'),
        );
    }

    private function createScheduledAppointment(
        string $reference,
        Patient $patient,
        Doctor $doctor,
        Specialty $specialty,
    ): void {
        if (Appointment::query()->where('reference', $reference)->exists()) {
            return;
        }

        $slot = $this->nextAvailableSlot($doctor);

        if ($slot === null) {
            $this->command?->warn("No hay turnos disponibles para crear la cita demo {$reference}.");

            return;
        }

        $slot->update(['status' => SlotStatus::RESERVED]);

        Appointment::query()->create([
            'reference' => $reference,
            'patient_id' => $patient->getKey(),
            'doctor_id' => $doctor->getKey(),
            'specialty_id' => $specialty->getKey(),
            'slot_id' => $slot->getKey(),
            'scheduled_at' => $slot->starts_at,
            'ends_at' => $slot->ends_at,
            'status' => AppointmentStatus::SCHEDULED,
        ]);
    }

    private function seedCanceledAppointment(): void
    {
        $reference = 'CV-DEMO-CANCELADA-01';

        if (Appointment::query()->where('reference', $reference)->exists()) {
            return;
        }

        $doctor = $this->doctor('MED-003');
        $slot = $this->nextAvailableSlot($doctor);

        if ($slot === null) {
            return;
        }

        Appointment::query()->create([
            'reference' => $reference,
            'patient_id' => $this->patient('10000003')->getKey(),
            'doctor_id' => $doctor->getKey(),
            'specialty_id' => $this->specialty('Cardiología')->getKey(),
            'slot_id' => null,
            'scheduled_at' => $slot->starts_at,
            'ends_at' => $slot->ends_at,
            'status' => AppointmentStatus::CANCELED,
            'canceled_at' => now(),
            'cancellation_reason' => 'Cancelación de demostración: el turno vuelve a estar disponible.',
        ]);
    }

    private function seedCompletedAppointment(): void
    {
        $reference = 'CV-DEMO-REALIZADA-01';

        if (Appointment::query()->where('reference', $reference)->exists()) {
            return;
        }

        $doctor = $this->doctor('MED-005');
        $slot = $this->pastSlot($doctor, '09:00');

        Appointment::query()->create([
            'reference' => $reference,
            'patient_id' => $this->patient('10000005')->getKey(),
            'doctor_id' => $doctor->getKey(),
            'specialty_id' => $this->specialty('Ginecología')->getKey(),
            'slot_id' => $slot->getKey(),
            'scheduled_at' => $slot->starts_at,
            'ends_at' => $slot->ends_at,
            'status' => AppointmentStatus::COMPLETED,
        ]);
    }

    private function seedMissedAppointment(): void
    {
        $reference = 'CV-DEMO-NO-ASISTIO-01';

        if (Appointment::query()->where('reference', $reference)->exists()) {
            return;
        }

        $doctor = $this->doctor('MED-001');
        $slot = $this->pastSlot($doctor, '10:00');

        $appointment = Appointment::query()->create([
            'reference' => $reference,
            'patient_id' => $this->patient('10000006')->getKey(),
            'doctor_id' => $doctor->getKey(),
            'specialty_id' => $this->specialty('Medicina General')->getKey(),
            'slot_id' => $slot->getKey(),
            'scheduled_at' => $slot->starts_at,
            'ends_at' => $slot->ends_at,
            'status' => AppointmentStatus::MISSED,
        ]);

        AppointmentFollowUp::query()->updateOrCreate(
            ['appointment_id' => $appointment->getKey()],
            [
                'status' => FollowUpStatus::PENDING_CALL,
                'notes' => 'Llamar al paciente para reprogramar la cita.',
            ],
        );
    }

    private function seedRescheduledAppointments(): void
    {
        $originalReference = 'CV-DEMO-REPROGRAMADA-ORIGINAL';
        $newReference = 'CV-DEMO-REPROGRAMADA-NUEVA';

        $doctor = $this->doctor('MED-004');
        $patient = $this->patient('10000004');
        $specialty = $this->specialty('Dermatología');

        $original = Appointment::query()->where('reference', $originalReference)->first();
        $newAppointment = Appointment::query()->where('reference', $newReference)->first();

        if ($newAppointment !== null) {
            return;
        }

        if ($original === null) {
            $oldSlot = $this->nextAvailableSlot($doctor);

            if ($oldSlot === null) {
                return;
            }

            $original = Appointment::query()->create([
                'reference' => $originalReference,
                'patient_id' => $patient->getKey(),
                'doctor_id' => $doctor->getKey(),
                'specialty_id' => $specialty->getKey(),
                'slot_id' => null,
                'scheduled_at' => $oldSlot->starts_at,
                'ends_at' => $oldSlot->ends_at,
                'status' => AppointmentStatus::RESCHEDULED,
            ]);
        }

        $newSlot = $this->nextAvailableSlot($doctor, $original->scheduled_at);

        if ($newSlot === null) {
            return;
        }

        $newSlot->update(['status' => SlotStatus::RESERVED]);

        Appointment::query()->create([
            'reference' => $newReference,
            'patient_id' => $original->patient_id,
            'doctor_id' => $original->doctor_id,
            'specialty_id' => $original->specialty_id,
            'slot_id' => $newSlot->getKey(),
            'scheduled_at' => $newSlot->starts_at,
            'ends_at' => $newSlot->ends_at,
            'status' => AppointmentStatus::SCHEDULED,
            'rescheduled_from_id' => $original->getKey(),
        ]);
    }

    private function nextAvailableSlot(Doctor $doctor, ?CarbonInterface $excludeStart = null): ?AppointmentSlot
    {
        return AppointmentSlot::query()
            ->where('doctor_id', $doctor->getKey())
            ->where('status', SlotStatus::AVAILABLE->value)
            ->where('starts_at', '>', now())
            ->when($excludeStart, fn ($query) => $query->where('starts_at', '>', $excludeStart))
            ->orderBy('starts_at')
            ->first();
    }

    private function pastSlot(Doctor $doctor, string $time): AppointmentSlot
    {
        $availability = Availability::query()
            ->where('doctor_id', $doctor->getKey())
            ->where('is_active', true)
            ->orderBy('weekday')
            ->first();

        $date = CarbonImmutable::now(config('app.timezone'))->subDay();

        if ($availability !== null) {
            while ($date->dayOfWeekIso !== $availability->weekday) {
                $date = $date->subDay();
            }

            $start = $date->setTimeFromTimeString($time);
            $availabilityStart = $date->setTimeFromTimeString($availability->start_time);
            $availabilityEnd = $date->setTimeFromTimeString($availability->end_time);

            if ($start->lt($availabilityStart) || $start->addMinutes(30)->gt($availabilityEnd)) {
                $start = $availabilityStart;
            }
        } else {
            $start = $date->setTimeFromTimeString($time);
        }

        return AppointmentSlot::query()->updateOrCreate(
            [
                'doctor_id' => $doctor->getKey(),
                'starts_at' => $start,
                'ends_at' => $start->addMinutes(30),
            ],
            [
                'status' => SlotStatus::RESERVED,
            ],
        );
    }

    private function doctor(string $code): Doctor
    {
        return Doctor::query()->where('internal_code', $code)->firstOrFail();
    }

    private function specialty(string $name): Specialty
    {
        return Specialty::query()->where('name', $name)->firstOrFail();
    }

    private function patient(string $document): Patient
    {
        return Patient::query()->where('document_number', $document)->firstOrFail();
    }
}
