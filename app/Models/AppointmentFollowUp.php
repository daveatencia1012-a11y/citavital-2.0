<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'status',
        'notes',
        'contacted_at',
        'managed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => FollowUpStatus::class,
            'contacted_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function scopePendingCall(Builder $query): Builder
    {
        return $query->where('status', FollowUpStatus::PENDING_CALL->value)
            ->whereHas('appointment', fn (Builder $appointment) => $appointment->where('status', AppointmentStatus::MISSED->value));
    }

    public function managedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by_id');
    }
}
