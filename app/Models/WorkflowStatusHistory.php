<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowStatusHistory extends Model
{
    protected $fillable = [
        'subject_type', 'subject_id', 'from_status', 'to_status',
        'user_id', 'quantity', 'notes', 'changed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'changed_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        Model $subject,
        ?string $fromStatus,
        string $toStatus,
        ?int $userId,
        ?string $notes = null,
        ?int $quantity = null
    ): self {
        return static::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'user_id' => $userId,
            'quantity' => $quantity,
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }
}
