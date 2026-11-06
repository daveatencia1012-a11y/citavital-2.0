<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'appointment_duration' => 30,
            'is_active' => true,
        ];
    }
}
