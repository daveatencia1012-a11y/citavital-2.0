<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'patient_id',
        'doctor_id',
        'specialty_id',
        'slot_id',
        'scheduled_at',
        'ends_at',
        'status',
        'rescheduled_from_id',
        'canceled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'canceled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment): void {
            if (blank($appointment->reference)) {
                $appointment->reference = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'CV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function rescheduledTo(): HasMany
    {
        return $this->hasMany(self::class, 'rescheduled_from_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(AppointmentFollowUp::class);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::SCHEDULED->value);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->where('scheduled_at', '>=', now());
    }

    public function scopeHistory(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('scheduled_at', '<', now())
                ->orWhere('status', '!=', AppointmentStatus::SCHEDULED->value);
        });
    }

    public function isScheduled(): bool
    {
        return $this->status === AppointmentStatus::SCHEDULED;
    }
}
