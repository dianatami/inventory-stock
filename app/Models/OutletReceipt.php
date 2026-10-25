<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutletReceipt extends Model
{
    protected $fillable = [
        'outlet_order_id', 'outlet_id', 'submitted_by', 'validated_by',
        'status', 'notes', 'submitted_at', 'validated_at',
    ];

    protected $casts = ['submitted_at' => 'datetime', 'validated_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OutletOrder::class, 'outlet_order_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutletReceiptItem::class);
    }
}
