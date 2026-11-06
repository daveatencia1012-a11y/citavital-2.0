<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case SCHEDULED = 'PROGRAMADA';
    case COMPLETED = 'REALIZADA';
    case CANCELED = 'CANCELADA';
    case MISSED = 'NO_ASISTIO';
    case RESCHEDULED = 'REPROGRAMADA';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Programada',
            self::COMPLETED => 'Realizada',
            self::CANCELED => 'Cancelada',
            self::MISSED => 'No asistió',
            self::RESCHEDULED => 'Reprogramada',
        };
    }

    public function isActive(): bool
    {
        return $this === self::SCHEDULED;
    }
}
