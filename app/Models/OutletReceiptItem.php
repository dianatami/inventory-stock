<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletReceiptItem extends Model
{
    protected $fillable = [
        'outlet_receipt_id', 'outlet_order_item_id',
        'received_quantity', 'validated_quantity',
    ];

    protected $casts = [
        'received_quantity' => 'integer',
        'validated_quantity' => 'integer',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(OutletReceipt::class, 'outlet_receipt_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OutletOrderItem::class, 'outlet_order_item_id');
    }
}
