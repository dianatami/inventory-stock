<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OutletOrder extends Model
{
    protected $fillable = [
        'order_number', 'outlet_id', 'user_id', 'status', 'notes',
        'decision_notes', 'reviewed_by', 'reviewed_at', 'dispatched_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime', 'dispatched_at' => 'datetime'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutletOrderItem::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(OutletReceipt::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OutletReturn::class);
    }
}
