<?php

namespace App\Enums;

enum FollowUpStatus: string
{
    case PENDING_CALL = 'PENDIENTE_LLAMADA';
    case CONTACTED = 'CONTACTADO';
    case RESCHEDULED = 'REPROGRAMADO';
    case CLOSED = 'CERRADO';
}
