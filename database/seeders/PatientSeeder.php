<?php

namespace Database\Seeders;

use App\Enums\InsuranceType;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            ['10000001', 'María', 'Cárdenas', '1985-03-14', InsuranceType::CONTRIBUTIVE, 'Calle 10 # 5-25', '3105550101', '6015550101'],
            ['10000002', 'José', 'Pérez', '1978-07-22', InsuranceType::SUBSIDIZED, 'Carrera 8 # 12-40', '3105550102', '6015550102'],
            ['10000003', 'Luisa', 'Fernández', '1992-11-05', InsuranceType::CONTRIBUTIVE, 'Calle 45 # 18-10', '3105550103', '6015550103'],
            ['10000004', 'Andrés', 'Salazar', '2001-01-30', InsuranceType::PRIVATE, 'Avenida 3 # 7-15', '3105550104', '6015550104'],
            ['10000005', 'Diana', 'Morales', '1969-09-18', InsuranceType::SUBSIDIZED, 'Calle 22 # 9-33', '3105550105', '6015550105'],
            ['10000006', 'Felipe', 'Castro', '1988-04-02', InsuranceType::CONTRIBUTIVE, 'Carrera 15 # 30-21', '3105550106', '6015550106'],
            ['10000007', 'Camila', 'Vargas', '1995-12-12', InsuranceType::OTHER, 'Calle 5 # 2-18', '3105550107', '6015550107'],
            ['10000008', 'Santiago', 'Mejía', '2010-06-08', InsuranceType::SUBSIDIZED, 'Carrera 7 # 14-52', '3105550108', '6015550108'],
            ['10000009', 'Valentina', 'Ortiz', '2015-02-26', InsuranceType::CONTRIBUTIVE, 'Calle 31 # 11-09', '3105550109', '6015550109'],
            ['10000010', 'Ricardo', 'Navarro', '1959-10-20', InsuranceType::PRIVATE, 'Avenida 6 # 25-14', '3105550110', '6015550110'],
        ];

        foreach ($patients as [$document, $firstName, $lastName, $birthDate, $insurance, $address, $primaryPhone, $secondaryPhone]) {
            Patient::query()->updateOrCreate(
                ['document_number' => $document],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'birth_date' => $birthDate,
                    'insurance_type' => $insurance->value,
                    'address' => $address,
                    'primary_phone' => $primaryPhone,
                    'secondary_phone' => $secondaryPhone,
                    'is_active' => true,
                ],
            );
        }
    }
}
