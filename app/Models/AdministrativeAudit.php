<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'entity_type',
        'entity_id',
        'operation',
        'old_status',
        'new_status',
        'notes',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
