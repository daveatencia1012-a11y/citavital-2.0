<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Models\Appointment;
use App\Models\AppointmentFollowUp;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StatisticsService
{
    public function summary(array $filters): array
    {
        $today = CarbonImmutable::now('America/Bogota')->startOfDay();
        $period = $filters['periodo'] ?? '30dias';
        [$start, $end] = match ($period) {
            'hoy' => [$today, $today],
            '7dias' => [$today->subDays(6), $today],
            'mes' => [$today->startOfMonth(), $today->endOfMonth()],
            'personalizado' => [CarbonImmutable::parse($filters['desde'], 'America/Bogota'), CarbonImmutable::parse($filters['hasta'], 'America/Bogota')],
            default => [$today->subDays(29), $today],
        };
        $start = $start->startOfDay();
        $end = $end->endOfDay();
        $base = Appointment::query()->whereBetween('scheduled_at', [$start, $end])
            ->when($filters['especialidad'] ?? null, fn ($q, $id) => $q->where('specialty_id', $id))
            ->when($filters['medico'] ?? null, fn ($q, $id) => $q->where('doctor_id', $id));
        $states = $this->states(clone $base);
        $history = $this->states(Appointment::query());
        $followUps = AppointmentFollowUp::query()->whereIn('appointment_id', (clone $base)->select('id'))
            ->whereHas('appointment', fn (Builder $query) => $query->where('status', AppointmentStatus::MISSED->value))
            ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $pending = AppointmentFollowUp::query()->pendingCall()
            ->whereIn('appointment_id', (clone $base)->select('id'))->count();
        $followRows = collect(FollowUpStatus::cases())->map(fn ($state) => [
            'label' => str_replace('_', ' ', $state->value), 'value' => (int) ($followUps[$state->value] ?? 0), 'status' => $state->value,
        ]);
        $monthly = $start->diffInDays($end) > 62;
        $expression = $monthly ? "DATE_FORMAT(scheduled_at, '%Y-%m')" : 'DATE(scheduled_at)';
        $evolution = (clone $base)->selectRaw("{$expression} AS period, COUNT(*) AS total")->groupBy('period')->orderBy('period')->pluck('total', 'period');
        $series = collect();
        for ($cursor = $monthly ? $start->startOfMonth() : $start; $cursor->lte($end); $cursor = $monthly ? $cursor->addMonth() : $cursor->addDay()) {
            $key = $cursor->format($monthly ? 'Y-m' : 'Y-m-d');
            $series->push(['label' => $key, 'value' => (int) ($evolution[$key] ?? 0)]);
        }
        $specialties = $this->grouped(clone $base, 'specialty_id', 'specialty');
        $doctors = $this->grouped(clone $base, 'doctor_id', 'doctor');

        return [
            'periodo' => $period, 'desde' => $start->toDateString(), 'hasta' => $end->toDateString(),
            'states' => $states, 'history' => $history, 'total' => $states->sum('value'),
            'effective' => $states->whereIn('status', ['PROGRAMADA', 'REALIZADA'])->sum('value'),
            'activePatients' => Patient::query()->where('is_active', true)->count(),
            'activeDoctors' => Doctor::query()->where('is_active', true)->count(),
            'pending' => $pending,
            'specialties' => $specialties, 'doctors' => $doctors,
            'evolution' => $series, 'granularity' => $monthly ? 'mes' : 'día',
            'followUps' => $followRows->prepend(['label' => 'Inasistencias', 'value' => $states->firstWhere('status', 'NO_ASISTIO')['value']]),
        ];
    }

    private function states(Builder $query): Collection
    {
        $counts = $query->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return collect(AppointmentStatus::cases())->map(fn ($state) => ['label' => $state->label(), 'status' => $state->value, 'value' => (int) ($counts[$state->value] ?? 0)]);
    }

    private function grouped(Builder $query, string $column, string $relation): Collection
    {
        // Columna, relación y orden proceden exclusivamente del código del servidor.
        $query->select($column)->selectRaw('COUNT(*) AS total');
        foreach (AppointmentStatus::cases() as $state) {
            $query->selectRaw('SUM(status = ?) AS '.$state->value, [$state->value]);
        }

        return $query->with($relation)->groupBy($column)->orderByDesc('total')->orderBy($column)->get()->map(function ($row) use ($relation): array {
            $result = ['label' => $relation === 'doctor' ? $row->$relation->full_name : $row->$relation->name, 'value' => (int) $row->total];
            foreach (AppointmentStatus::cases() as $state) {
                $result[$state->value] = (int) $row->{$state->value};
            }

            return $result;
        });
    }
}
