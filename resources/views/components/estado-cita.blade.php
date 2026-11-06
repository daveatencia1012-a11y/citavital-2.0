@props(['estado'])

@php
    $estado = $estado instanceof \App\Enums\AppointmentStatus
        ? $estado
        : \App\Enums\AppointmentStatus::from($estado);

    $tono = match ($estado) {
        \App\Enums\AppointmentStatus::SCHEDULED => 'programada',
        \App\Enums\AppointmentStatus::COMPLETED => 'realizada',
        \App\Enums\AppointmentStatus::CANCELED => 'cancelada',
        \App\Enums\AppointmentStatus::MISSED => 'inasistencia',
        \App\Enums\AppointmentStatus::RESCHEDULED => 'reprogramada',
    };
@endphp

<span class="admin-estado admin-estado--{{ $tono }}">{{ $estado->label() }}</span>
