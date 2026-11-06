<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            [
                'internal_code' => 'MED-001',
                'first_name' => 'Andrea',
                'last_name' => 'Martínez',
                'specialties' => ['Medicina General'],
            ],
            [
                'internal_code' => 'MED-002',
                'first_name' => 'Carlos',
                'last_name' => 'Rojas',
                'specialties' => ['Pediatría', 'Medicina General'],
            ],
            [
                'internal_code' => 'MED-003',
                'first_name' => 'Laura',
                'last_name' => 'Gómez',
                'specialties' => ['Cardiología'],
            ],
            [
                'internal_code' => 'MED-004',
                'first_name' => 'Diego',
                'last_name' => 'Herrera',
                'specialties' => ['Dermatología'],
            ],
            [
                'internal_code' => 'MED-005',
                'first_name' => 'Paula',
                'last_name' => 'Ramírez',
                'specialties' => ['Ginecología'],
            ],
        ];

        foreach ($doctors as $data) {
            $doctor = Doctor::query()->updateOrCreate(
                ['internal_code' => $data['internal_code']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'is_active' => true,
                ],
            );

            $specialtyIds = Specialty::query()
                ->whereIn('name', $data['specialties'])
                ->pluck('id')
                ->all();

            $doctor->specialties()->sync($specialtyIds);
        }
    }
}
