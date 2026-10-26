<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletReturnItem extends Model
{
    protected $fillable = [
        'outlet_return_id', 'product_id', 'requested_quantity',
        'shipped_quantity', 'verified_quantity', 'condition', 'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'shipped_quantity' => 'integer',
        'verified_quantity' => 'integer',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(OutletReturn::class, 'outlet_return_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
