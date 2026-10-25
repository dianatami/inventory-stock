<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletOrderItem extends Model
{
    protected $fillable = [
        'outlet_order_id', 'product_id', 'requested_quantity',
        'approved_quantity', 'dispatched_quantity',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'approved_quantity' => 'integer',
        'dispatched_quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OutletOrder::class, 'outlet_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
