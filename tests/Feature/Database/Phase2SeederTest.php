<?php

namespace Tests\Feature\Database;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase2SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_the_requested_catalog_and_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(5, Specialty::query()->count());
        $this->assertSame(5, Doctor::query()->count());
        $this->assertSame(10, Patient::query()->count());

        $this->assertDatabaseHas('appointments', ['status' => AppointmentStatus::SCHEDULED->value]);
        $this->assertDatabaseHas('appointments', ['status' => AppointmentStatus::COMPLETED->value]);
        $this->assertDatabaseHas('appointments', ['status' => AppointmentStatus::CANCELED->value]);
        $this->assertDatabaseHas('appointments', ['status' => AppointmentStatus::MISSED->value]);
        $this->assertDatabaseHas('appointments', ['status' => AppointmentStatus::RESCHEDULED->value]);
        $this->assertDatabaseHas('appointment_follow_ups', ['status' => FollowUpStatus::PENDING_CALL->value]);

        $counts = [
            'specialties' => Specialty::query()->count(),
            'doctors' => Doctor::query()->count(),
            'patients' => Patient::query()->count(),
            'appointments' => Appointment::query()->count(),
        ];

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($counts['specialties'], Specialty::query()->count());
        $this->assertSame($counts['doctors'], Doctor::query()->count());
        $this->assertSame($counts['patients'], Patient::query()->count());
        $this->assertSame($counts['appointments'], Appointment::query()->count());
    }

    public function test_admin_seeder_uses_the_configured_password(): void
    {
        $password = Str::random(24);
        config(['citavital.admin_password' => $password]);

        $this->seed(AdminUserSeeder::class);

        $user = User::query()->where('email', config('citavital.admin_email'))->firstOrFail();

        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_admin_seeder_does_not_create_a_user_without_a_password(): void
    {
        config(['citavital.admin_password' => null]);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseMissing('users', [
            'email' => config('citavital.admin_email'),
        ]);
    }
}
