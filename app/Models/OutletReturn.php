<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OutletReturn extends Model
{
    protected $fillable = [
        'return_number', 'outlet_order_id', 'outlet_receipt_id', 'outlet_id',
        'requested_by', 'reviewed_by', 'dispatched_by', 'verified_by',
        'status', 'reason', 'decision_notes', 'reviewed_at', 'dispatched_at', 'verified_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OutletOrder::class, 'outlet_order_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(OutletReceipt::class, 'outlet_receipt_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutletReturnItem::class);
    }

    public function statusHistory(): MorphMany
    {
        return $this->morphMany(WorkflowStatusHistory::class, 'subject');
    }
}
