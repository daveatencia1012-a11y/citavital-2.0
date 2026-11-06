<?php

namespace App\Enums;

enum InsuranceType: string
{
    case CONTRIBUTIVE = 'CONTRIBUTIVO';
    case SUBSIDIZED = 'SUBSIDIADO';
    case PRIVATE = 'PARTICULAR';
    case OTHER = 'OTRO';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CONTRIBUTIVE => 'Contributivo',
            self::SUBSIDIZED => 'Subsidiado',
            self::PRIVATE => 'Particular',
            self::OTHER => 'Otro',
        };
    }
}
