<?php

namespace App\Enums;

enum SlotStatus: string
{
    case AVAILABLE = 'DISPONIBLE';
    case RESERVED = 'RESERVADO';
    case RELEASED = 'LIBERADO';
}
