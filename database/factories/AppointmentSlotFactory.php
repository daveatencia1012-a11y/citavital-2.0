<?php

namespace Database\Factories;

use App\Enums\SlotStatus;
use App\Models\AppointmentSlot;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentSlot>
 */
class AppointmentSlotFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDay()->setTime(9, 0);

        return [
            'doctor_id' => Doctor::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
            'status' => SlotStatus::AVAILABLE,
        ];
    }
}
