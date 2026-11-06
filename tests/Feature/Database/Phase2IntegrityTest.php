<?php

namespace Tests\Feature\Database;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase2IntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_document_number_must_be_unique(): void
    {
        Patient::factory()->create(['document_number' => '12345678']);

        $this->expectException(QueryException::class);

        Patient::factory()->create(['document_number' => '12345678']);
    }

    public function test_mysql_patient_document_check_accepts_only_8_or_10_digits(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('La restricción CHECK de cédula solo aplica a MySQL.');
        }

        Patient::factory()->create(['document_number' => '1234567890']);

        $this->expectException(QueryException::class);

        Patient::factory()->create(['document_number' => '1234567']);
    }

    public function test_both_phone_numbers_are_required(): void
    {
        $this->expectException(QueryException::class);

        Patient::factory()->create([
            'primary_phone' => null,
            'secondary_phone' => null,
        ]);
    }

    public function test_patient_age_is_calculated_from_birth_date(): void
    {
        $birthDate = now()->subYears(40)->subDay();

        $patient = Patient::factory()->create([
            'birth_date' => $birthDate->toDateString(),
        ]);

        $this->assertSame(40, $patient->age);
    }

    public function test_doctor_and_specialty_have_a_many_to_many_relationship(): void
    {
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $doctor->specialties()->attach($specialty);

        $this->assertTrue($doctor->specialties()->whereKey($specialty->getKey())->exists());
        $this->assertTrue($specialty->doctors()->whereKey($doctor->getKey())->exists());
    }

    public function test_availability_rejects_an_invalid_hour_range(): void
    {
        [$doctor] = $this->doctorWithAvailability();

        $this->expectException(ValidationException::class);

        app(AvailabilityService::class)->createAvailability($doctor, 1, '12:00', '08:00', 30);
    }

    public function test_availability_rejects_a_partial_overlap(): void
    {
        [$doctor] = $this->doctorWithAvailability();

        $this->expectException(ValidationException::class);

        app(AvailabilityService::class)->createAvailability($doctor, 1, '11:00', '13:00', 30);
    }

    public function test_slot_generation_creates_non_overlapping_slots(): void
    {
        [, , $availability] = $this->doctorWithAvailability();
        $monday = $this->futureMonday();

        $slots = app(AvailabilityService::class)->generateSlots($availability, $monday, $monday);

        $this->assertCount(8, $slots);
        $this->assertDatabaseCount('appointment_slots', 8);
    }

    public function test_partial_slot_overlap_is_rejected_by_the_service(): void
    {
        [$doctor] = $this->doctorWithAvailability();
        $start = $this->futureMonday(8);

        $this->slot($doctor, $start);

        $this->expectException(ValidationException::class);

        app(AvailabilityService::class)->assertSlotDoesNotOverlap(
            $doctor->getKey(),
            $start->addMinutes(15),
            $start->addMinutes(45),
        );
    }

    public function test_mysql_trigger_rejects_a_partial_slot_overlap(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('La prueba del trigger solo aplica a MySQL.');
        }

        $trigger = DB::selectOne(
            "SELECT COUNT(*) AS total
             FROM information_schema.TRIGGERS
             WHERE TRIGGER_SCHEMA = DATABASE()
               AND TRIGGER_NAME = 'trg_appointment_slots_no_overlap_bi'"
        );

        if ((int) $trigger->total === 0) {
            $this->markTestSkipped('El trigger no está disponible: el usuario MySQL no tiene privilegio SUPER.');
        }

        [$doctor] = $this->doctorWithAvailability();
        $start = $this->futureMonday(8);

        $this->slot($doctor, $start);

        $this->expectException(QueryException::class);

        AppointmentSlot::query()->create([
            'doctor_id' => $doctor->getKey(),
            'starts_at' => $start->addMinutes(15),
            'ends_at' => $start->addMinutes(45),
            'status' => SlotStatus::AVAILABLE,
        ]);
    }

    public function test_a_slot_cannot_be_booked_twice(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $slot = $this->slot($doctor, $this->futureMonday());

        app(AppointmentService::class)->book($slot, Patient::factory()->create(), $specialty);

        $this->expectException(ValidationException::class);

        app(AppointmentService::class)->book($slot, Patient::factory()->create(), $specialty);
    }

    public function test_booking_reserves_the_slot_and_creates_an_audit_record(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $slot = $this->slot($doctor, $this->futureMonday());

        $appointment = app(AppointmentService::class)->book(
            $slot,
            Patient::factory()->create(),
            $specialty,
        );

        $this->assertSame(AppointmentStatus::SCHEDULED, $appointment->status);
        $this->assertSame(SlotStatus::RESERVED, $slot->fresh()->status);
        $this->assertStringStartsWith('CV-', $appointment->reference);

        $this->assertDatabaseHas('administrative_audits', [
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->getKey(),
            'operation' => 'CREATE',
        ]);
    }

    public function test_booking_rejects_a_slot_outside_availability(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $slot = $this->slot($doctor, $this->futureMonday(13));

        $this->expectException(ValidationException::class);

        app(AppointmentService::class)->book($slot, Patient::factory()->create(), $specialty);
    }

    public function test_booking_rejects_a_blocked_slot(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $start = $this->futureMonday();
        $slot = $this->slot($doctor, $start);

        AvailabilityBlock::query()->create([
            'doctor_id' => $doctor->getKey(),
            'starts_at' => $start,
            'ends_at' => $start->addMinutes(30),
            'reason' => 'Bloqueo de prueba',
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(AppointmentService::class)->book($slot, Patient::factory()->create(), $specialty);
    }

    public function test_booking_rejects_a_slot_in_the_past(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $slot = $this->slot($doctor, CarbonImmutable::now(config('app.timezone'))->subDay()->setTime(9, 0));

        $this->expectException(ValidationException::class);

        app(AppointmentService::class)->book($slot, Patient::factory()->create(), $specialty);
    }

    public function test_booking_rejects_a_specialty_not_associated_with_the_doctor(): void
    {
        [$doctor] = $this->doctorWithAvailability();
        $slot = $this->slot($doctor, $this->futureMonday());
        $otherSpecialty = Specialty::factory()->create();

        $this->expectException(ValidationException::class);

        app(AppointmentService::class)->book($slot, Patient::factory()->create(), $otherSpecialty);
    }

    public function test_booking_rejects_an_inactive_doctor(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $doctor->update(['is_active' => false]);
        $slot = $this->slot($doctor, $this->futureMonday());

        $this->expectException(ValidationException::class);

        app(AppointmentService::class)->book($slot, Patient::factory()->create(), $specialty);
    }

    public function test_cancellation_releases_the_slot_for_a_new_booking(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $slot = $this->slot($doctor, $this->futureMonday());
        $service = app(AppointmentService::class);

        $appointment = $service->book($slot, Patient::factory()->create(), $specialty);
        $service->cancel($appointment, 'Cancelación de prueba');

        $this->assertSame(AppointmentStatus::CANCELED, $appointment->fresh()->status);
        $this->assertNull($appointment->fresh()->slot_id);
        $this->assertSame(SlotStatus::AVAILABLE, $slot->fresh()->status);

        $newAppointment = $service->book($slot, Patient::factory()->create(), $specialty);

        $this->assertSame(AppointmentStatus::SCHEDULED, $newAppointment->status);
        $this->assertSame(SlotStatus::RESERVED, $slot->fresh()->status);
    }

    public function test_rescheduling_preserves_the_original_and_creates_a_new_appointment(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $firstSlot = $this->slot($doctor, $this->futureMonday(9));
        $secondSlot = $this->slot($doctor, $this->futureMonday(10));
        $service = app(AppointmentService::class);

        $original = $service->book($firstSlot, Patient::factory()->create(), $specialty);
        $newAppointment = $service->reschedule($original, $secondSlot);

        $original->refresh();

        $this->assertSame(AppointmentStatus::RESCHEDULED, $original->status);
        $this->assertNull($original->slot_id);
        $this->assertSame(SlotStatus::AVAILABLE, $firstSlot->fresh()->status);

        $this->assertSame(AppointmentStatus::SCHEDULED, $newAppointment->status);
        $this->assertSame($original->getKey(), $newAppointment->rescheduled_from_id);
        $this->assertSame(SlotStatus::RESERVED, $secondSlot->fresh()->status);
        $this->assertSame($original->patient_id, $newAppointment->patient_id);
    }

    public function test_marking_a_missed_appointment_creates_a_pending_follow_up(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $slot = $this->slot($doctor, $this->futureMonday());
        $service = app(AppointmentService::class);

        $appointment = $service->book($slot, Patient::factory()->create(), $specialty);
        $this->travelTo($appointment->ends_at->copy()->addMinute());
        $appointment = $service->markMissed($appointment);

        $this->assertSame(AppointmentStatus::MISSED, $appointment->status);
        $this->assertDatabaseHas('appointment_follow_ups', [
            'appointment_id' => $appointment->getKey(),
            'status' => FollowUpStatus::PENDING_CALL->value,
        ]);
    }

    public function test_foreign_key_prevents_deleting_a_patient_with_appointments(): void
    {
        [$doctor, $specialty] = $this->doctorWithAvailability();
        $slot = $this->slot($doctor, $this->futureMonday());
        $patient = Patient::factory()->create();

        app(AppointmentService::class)->book($slot, $patient, $specialty);

        $this->expectException(QueryException::class);

        $patient->delete();
    }

    /**
     * @return array{0: Doctor, 1: Specialty, 2: Availability}
     */
    private function doctorWithAvailability(): array
    {
        $specialty = Specialty::factory()->create();
        $doctor = Doctor::factory()->create();
        $doctor->specialties()->attach($specialty);

        $availability = Availability::factory()->create([
            'doctor_id' => $doctor->getKey(),
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
        ]);

        return [$doctor, $specialty, $availability];
    }

    private function futureMonday(int $hour = 9): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))
            ->next('Monday')
            ->setTime($hour, 0);
    }

    private function slot(Doctor $doctor, CarbonInterface $start): AppointmentSlot
    {
        return AppointmentSlot::factory()->create([
            'doctor_id' => $doctor->getKey(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
            'status' => SlotStatus::AVAILABLE,
        ]);
    }
}
