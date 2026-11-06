<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            ['name' => 'Medicina General', 'description' => 'Atención médica integral y remisión a especialidades.'],
            ['name' => 'Pediatría', 'description' => 'Atención médica para niñas, niños y adolescentes.'],
            ['name' => 'Cardiología', 'description' => 'Diagnóstico y seguimiento de enfermedades cardiovasculares.'],
            ['name' => 'Dermatología', 'description' => 'Diagnóstico y tratamiento de afecciones de la piel.'],
            ['name' => 'Ginecología', 'description' => 'Atención integral de la salud femenina.'],
        ];

        foreach ($specialties as $specialty) {
            Specialty::query()->updateOrCreate(
                ['name' => $specialty['name']],
                [
                    'description' => $specialty['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
