<?php

namespace Database\Seeders;

use App\Models\Availability;
use App\Models\AvailabilityBlock;
use App\Models\Doctor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            'MED-001' => [1, 2, 3, 4, 5],
            'MED-002' => [1, 3, 5],
            'MED-003' => [2, 4],
            'MED-004' => [1, 2],
            'MED-005' => [3, 4],
        ];

        $timeRanges = [
            'MED-001' => ['08:00:00', '12:00:00'],
            'MED-002' => ['13:00:00', '17:00:00'],
            'MED-003' => ['09:00:00', '12:00:00'],
            'MED-004' => ['14:00:00', '18:00:00'],
            'MED-005' => ['08:00:00', '11:00:00'],
        ];

        foreach ($schedules as $code => $weekdays) {
            $doctor = Doctor::query()->where('internal_code', $code)->firstOrFail();
            [$startTime, $endTime] = $timeRanges[$code];

            foreach ($weekdays as $weekday) {
                Availability::query()->updateOrCreate(
                    [
                        'doctor_id' => $doctor->getKey(),
                        'weekday' => $weekday,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                    [
                        'appointment_duration' => 30,
                        'is_active' => true,
                    ],
                );
            }
        }

        $blockedDoctor = Doctor::query()->where('internal_code', 'MED-003')->firstOrFail();
        $nextWednesday = CarbonImmutable::now(config('app.timezone'))
            ->next('Wednesday')
            ->setTime(10, 0);

        AvailabilityBlock::query()->updateOrCreate(
            [
                'doctor_id' => $blockedDoctor->getKey(),
                'starts_at' => $nextWednesday,
            ],
            [
                'ends_at' => $nextWednesday->addHours(2),
                'reason' => 'Bloqueo de agenda de demostración',
                'is_active' => true,
            ],
        );
    }
}
