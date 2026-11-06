<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentFollowUp;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class DashboardService
{
    /**
     * @return array{
     *     metrics: array<string, int>,
     *     todayAppointments: Collection<int, Appointment>,
     *     pendingFollowUps: Collection<int, AppointmentFollowUp>,
     *     currentDate: string
     * }
     */
    public function summary(): array
    {
        $now = now(config('app.timezone'));

        $todayAppointments = Appointment::query()
            ->with([
                'patient:id,first_name,last_name',
                'doctor:id,first_name,last_name',
                'specialty:id,name',
            ])
            ->whereBetween('scheduled_at', [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ])
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();

        $pendingFollowUps = AppointmentFollowUp::query()
            ->with([
                'appointment:id,reference,patient_id,doctor_id,scheduled_at,status',
                'appointment.patient:id,first_name,last_name',
                'appointment.doctor:id,first_name,last_name',
            ])
            ->pendingCall()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return [
            'metrics' => [
                'patients' => Patient::query()->count(),
                'activeDoctors' => Doctor::query()->where('is_active', true)->count(),
                'scheduledAppointments' => Appointment::query()
                    ->where('status', AppointmentStatus::SCHEDULED->value)
                    ->count(),
                'completedAppointments' => Appointment::query()
                    ->where('status', AppointmentStatus::COMPLETED->value)
                    ->count(),
                'canceledAppointments' => Appointment::query()
                    ->where('status', AppointmentStatus::CANCELED->value)
                    ->count(),
                'missedAppointments' => Appointment::query()
                    ->where('status', AppointmentStatus::MISSED->value)
                    ->count(),
                'todayAppointments' => $todayAppointments->count(),
            ],
            'todayAppointments' => $todayAppointments,
            'pendingFollowUps' => $pendingFollowUps,
            'currentDate' => Str::ucfirst(
                $now->copy()
                    ->locale('es')
                    ->translatedFormat('l, j \d\e F \d\e Y')
            ),
        ];
    }
}
